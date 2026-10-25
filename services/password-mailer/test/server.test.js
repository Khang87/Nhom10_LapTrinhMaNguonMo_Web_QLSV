import assert from 'node:assert/strict';
import { createHmac } from 'node:crypto';
import { after, before, describe, it } from 'node:test';
import { createPasswordMailerServer } from '../server.js';

const secret = 'test-secret-with-at-least-thirty-two-characters';
let server;
let baseUrl;
let sentMessages;

before(async () => {
  sentMessages = [];
  server = createPasswordMailerServer({
    secret,
    fromAddress: 'no-reply@example.com',
    transporter: {
      async sendMail(message) {
        sentMessages.push(message);
      },
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  baseUrl = `http://127.0.0.1:${server.address().port}`;
});

after(async () => {
  await new Promise((resolve, reject) => {
    server.close((error) => error ? reject(error) : resolve());
  });
});

function makePayload(overrides = {}) {
  return {
    email: 'student@example.com',
    otp: '123456',
    expiresInMinutes: 15,
    ...overrides,
  };
}

async function sendSignedRequest(payload, { timestamp = `${Math.floor(Date.now() / 1000)}`, signature } = {}) {
  const body = JSON.stringify(payload);
  const requestSignature = signature ?? createHmac('sha256', secret)
    .update(`${timestamp}.${body}`)
    .digest('hex');

  return fetch(`${baseUrl}/send-password-reset-otp`, {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      'x-reset-timestamp': timestamp,
      'x-reset-signature': requestSignature,
    },
    body,
  });
}

describe('password mailer endpoint', () => {
  it('sends a six-digit OTP email only to the signed recipient', async () => {
    const response = await sendSignedRequest(makePayload());

    assert.equal(response.status, 202);
    assert.equal(sentMessages.length, 1);
    assert.equal(sentMessages[0].from.address, 'no-reply@example.com');
    assert.equal(sentMessages[0].to, 'student@example.com');
    assert.match(sentMessages[0].text, /123456/);
    assert.match(sentMessages[0].text, /15 phút/);
  });

  it('rejects an invalid signature without sending mail', async () => {
    const countBefore = sentMessages.length;
    const response = await sendSignedRequest(makePayload(), { signature: '0'.repeat(64) });

    assert.equal(response.status, 401);
    assert.equal(sentMessages.length, countBefore);
  });

  it('rejects non-six-digit OTP payloads', async () => {
    const response = await sendSignedRequest(makePayload({ otp: '12<456' }));

    assert.equal(response.status, 400);
    assert.equal(sentMessages.length, 1);
  });

  it('rejects expired signed requests', async () => {
    const oldTimestamp = `${Math.floor(Date.now() / 1000) - 301}`;
    const response = await sendSignedRequest(makePayload(), { timestamp: oldTimestamp });

    assert.equal(response.status, 401);
    assert.equal(sentMessages.length, 1);
  });

  it('rejects requests that are not JSON', async () => {
    const response = await fetch(`${baseUrl}/send-password-reset-otp`, { method: 'POST' });

    assert.equal(response.status, 415);
    assert.equal(sentMessages.length, 1);
  });

  it('returns a generic delivery error when SMTP fails', async () => {
    const failingServer = createPasswordMailerServer({
      secret,
      fromAddress: 'no-reply@example.com',
      transporter: {
        async sendMail() {
          throw Object.assign(new Error('private SMTP response'), { code: 'EAUTH' });
        },
      },
      logger: { error() {} },
    });
    await new Promise((resolve) => failingServer.listen(0, '127.0.0.1', resolve));

    try {
      const port = failingServer.address().port;
      const timestamp = `${Math.floor(Date.now() / 1000)}`;
      const body = JSON.stringify(makePayload());
      const signature = createHmac('sha256', secret).update(`${timestamp}.${body}`).digest('hex');
      const response = await fetch(`http://127.0.0.1:${port}/send-password-reset-otp`, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'x-reset-timestamp': timestamp,
          'x-reset-signature': signature,
        },
        body,
      });

      assert.equal(response.status, 502);
      assert.deepEqual(await response.json(), { error: 'Email delivery failed.' });
    } finally {
      await new Promise((resolve, reject) => {
        failingServer.close((error) => error ? reject(error) : resolve());
      });
    }
  });
});
