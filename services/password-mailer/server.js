import { createHmac, timingSafeEqual } from 'node:crypto';
import http from 'node:http';
import { fileURLToPath } from 'node:url';
import { resolve } from 'node:path';
import nodemailer from 'nodemailer';

const ENDPOINT = '/send-password-reset-otp';
const MAX_BODY_BYTES = 8 * 1024;
const MAX_CLOCK_SKEW_SECONDS = 300;

class RequestError extends Error {
  constructor(statusCode, message) {
    super(message);
    this.statusCode = statusCode;
  }
}

function jsonResponse(response, statusCode, body) {
  response.writeHead(statusCode, { 'content-type': 'application/json; charset=utf-8' });
  response.end(JSON.stringify(body));
}

async function readRequestBody(request) {
  const chunks = [];
  let size = 0;

  for await (const chunk of request) {
    size += chunk.length;

    if (size > MAX_BODY_BYTES) {
      throw new RequestError(413, 'Request body is too large.');
    }

    chunks.push(chunk);
  }

  return Buffer.concat(chunks).toString('utf8');
}

function hasValidSignature({ timestamp, signature, rawBody, secret, now }) {
  if (!/^\d{10}$/.test(timestamp) || !/^[a-f\d]{64}$/i.test(signature)) {
    return false;
  }

  if (Math.abs(now - Number(timestamp)) > MAX_CLOCK_SKEW_SECONDS) {
    return false;
  }

  const expected = createHmac('sha256', secret)
    .update(`${timestamp}.${rawBody}`)
    .digest();
  const received = Buffer.from(signature, 'hex');

  return received.length === expected.length && timingSafeEqual(received, expected);
}

function validatePayload(payload) {
  if (payload === null || typeof payload !== 'object' || Array.isArray(payload)) {
    throw new RequestError(400, 'Invalid request body.');
  }

  const expectedKeys = ['email', 'expiresInMinutes', 'otp'];
  const keys = Object.keys(payload).sort();

  if (keys.length !== expectedKeys.length || !expectedKeys.every((key, index) => keys[index] === key)) {
    throw new RequestError(400, 'Invalid request fields.');
  }

  if (
    typeof payload.email !== 'string'
    || payload.email.length > 254
    || !/^[^\s@<>]+@[^\s@<>]+\.[^\s@<>]+$/.test(payload.email)
  ) {
    throw new RequestError(400, 'Invalid recipient address.');
  }

  if (typeof payload.otp !== 'string' || !/^\d{6}$/.test(payload.otp)) {
    throw new RequestError(400, 'OTP must contain six digits.');
  }

  if (!Number.isInteger(payload.expiresInMinutes) || payload.expiresInMinutes !== 15) {
    throw new RequestError(400, 'Invalid token expiry.');
  }

  return payload;
}

export function createPasswordMailerServer({
  secret,
  fromAddress,
  fromName = 'Quản lý sinh viên',
  transporter,
  logger = console,
  now = () => Math.floor(Date.now() / 1000),
}) {
  if (typeof secret !== 'string' || secret.length < 32) {
    throw new Error('NODEMAILER_SHARED_SECRET must contain at least 32 characters.');
  }
  if (typeof fromAddress !== 'string' || fromAddress.length === 0) {
    throw new Error('MAIL_FROM_ADDRESS must be configured.');
  }

  return http.createServer(async (request, response) => {
    if (request.method !== 'POST' || request.url !== ENDPOINT) {
      jsonResponse(response, 404, { error: 'Not found.' });
      return;
    }

    if (request.headers['content-type']?.split(';', 1)[0].trim().toLowerCase() !== 'application/json') {
      jsonResponse(response, 415, { error: 'Content-Type must be application/json.' });
      return;
    }

    try {
      const rawBody = await readRequestBody(request);
      const timestamp = request.headers['x-reset-timestamp'] ?? '';
      const signature = request.headers['x-reset-signature'] ?? '';

      if (!hasValidSignature({ timestamp, signature, rawBody, secret, now: now() })) {
        throw new RequestError(401, 'Request signature is invalid or expired.');
      }

      let payload;
      try {
        payload = JSON.parse(rawBody);
      } catch {
        throw new RequestError(400, 'Request body must be valid JSON.');
      }

      const { email, otp, expiresInMinutes } = validatePayload(payload);
      const message = {
        from: { name: fromName, address: fromAddress },
        to: email,
        subject: 'Mã OTP đặt lại mật khẩu - Quản lý sinh viên',
        text: `Mã OTP đặt lại mật khẩu của bạn là ${otp}. Mã có hiệu lực trong ${expiresInMinutes} phút và chỉ được sử dụng một lần.\n\nNếu bạn không yêu cầu mã này, hãy bỏ qua email.`,
        html: `<p>Mã OTP đặt lại mật khẩu của bạn là:</p><p><strong style="font-size:24px;letter-spacing:4px">${otp}</strong></p><p>Mã có hiệu lực trong ${expiresInMinutes} phút và chỉ được sử dụng một lần.</p><p>Nếu bạn không yêu cầu mã này, hãy bỏ qua email.</p>`,
      };

      await transporter.sendMail(message);
      jsonResponse(response, 202, { message: 'Password reset email accepted.' });
    } catch (error) {
      if (error instanceof RequestError) {
        jsonResponse(response, error.statusCode, { error: error.message });
        return;
      }

      logger.error('Password reset email delivery failed.', {
        name: error instanceof Error ? error.name : 'UnknownError',
        code: error?.code,
      });
      jsonResponse(response, 502, { error: 'Email delivery failed.' });
    }
  });
}

function requiredEnvironment(name) {
  const value = process.env[name]?.trim();

  if (!value) {
    throw new Error(`${name} must be configured.`);
  }

  return value;
}

function startService() {
  const secret = requiredEnvironment('NODEMAILER_SHARED_SECRET');
  const smtpHost = requiredEnvironment('SMTP_HOST');
  const smtpUser = requiredEnvironment('SMTP_USER');
  const smtpPassword = requiredEnvironment('SMTP_PASSWORD');
  const fromAddress = requiredEnvironment('MAIL_FROM_ADDRESS');
  const fromName = process.env.MAIL_FROM_NAME?.trim() || 'Quản lý sinh viên';
  const port = Number(process.env.SMTP_PORT ?? 587);
  const servicePort = Number(process.env.NODEMAILER_PORT ?? 3001);

  if (!Number.isInteger(port) || port < 1 || port > 65535 || !Number.isInteger(servicePort) || servicePort < 1 || servicePort > 65535) {
    throw new Error('SMTP_PORT and NODEMAILER_PORT must be valid TCP ports.');
  }

  const transporter = nodemailer.createTransport({
    host: smtpHost,
    port,
    secure: process.env.SMTP_SECURE === 'true',
    auth: { user: smtpUser, pass: smtpPassword },
  });
  const server = createPasswordMailerServer({
    secret,
    fromAddress,
    fromName,
    transporter,
  });

  server.listen(servicePort, '127.0.0.1', () => {
    console.log(`Nodemailer password service listening on 127.0.0.1:${servicePort}`);
  });
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  startService();
}
