"""Ve hai sơ đồ cho báo cáo: ERD và UML Use Case.

Chay:  python docs/diagrams/make_diagrams.py
Xuat:  docs/diagrams/erd.png, docs/diagrams/usecase.png
"""

import math

from PIL import Image, ImageDraw, ImageFont

ARIAL = "C:/Windows/Fonts/arial.ttf"
ARIAL_BOLD = "C:/Windows/Fonts/arialbd.ttf"

NAVY = (31, 59, 87)
BLUE = (46, 117, 182)
GREEN = (46, 125, 82)
AMBER = (176, 108, 20)
TEXT = (38, 55, 70)
MUTED = (96, 112, 128)
RAIL = (140, 155, 170)
BG = (247, 250, 252)
WHITE = (255, 255, 255)


def font(size, bold=False):
    return ImageFont.truetype(ARIAL_BOLD if bold else ARIAL, size)


def centered(draw, cx, cy, text, size, fill, bold=False):
    draw.text((cx, cy), text, font=font(size, bold), fill=fill, anchor="mm")


# ---------------------------------------------------------------- ERD

def entity(draw, x, y, w, h, name, columns):
    draw.rounded_rectangle([x, y, x + w, y + h], radius=10, fill=WHITE, outline=BLUE, width=3)
    draw.rounded_rectangle([x, y, x + w, y + 46], radius=10, fill=NAVY)
    draw.rectangle([x, y + 26, x + w, y + 46], fill=NAVY)
    draw.text((x + 18, y + 23), name, font=font(24, True), fill=WHITE, anchor="lm")

    row = y + 76
    for key, label in columns:
        indent = x + 18 if key else x + 52
        if key:
            draw.text((indent, row), key, font=font(19, True), fill=NAVY, anchor="lm")
            draw.text((indent + 34, row), label, font=font(19), fill=TEXT, anchor="lm")
        else:
            draw.text((indent, row), label, font=font(19), fill=TEXT, anchor="lm")
        row += 33


def dot(draw, cx, cy):
    draw.ellipse([cx - 5, cy - 5, cx + 5, cy + 5], fill=BLUE)


def connector(draw, start, end, label_start, label_end):
    draw.line([start, end], fill=BLUE, width=3)
    dot(draw, *start)
    dot(draw, *end)
    horizontal = start[1] == end[1]
    for point, label in ((start, label_start), (end, label_end)):
        if horizontal:
            offset = 16 if point is start else -16
            centered(draw, point[0] + offset, point[1] - 20, label, 19, NAVY, bold=True)
        else:
            centered(draw, point[0] + 20, point[1] + (22 if end[1] > start[1] else -22), label, 19, NAVY, bold=True)


def route(draw, points, label_start, label_end):
    """Duong gap khuc noi 2 bang, nhan o dau duoi doc thang."""
    draw.line(points, fill=BLUE, width=3)
    dot(draw, *points[0])
    dot(draw, *points[-1])
    centered(draw, points[0][0] + 24, points[0][1] + 13, label_start, 19, NAVY, bold=True)
    centered(draw, points[-1][0] - 24, points[-1][1] + 13, label_end, 19, NAVY, bold=True)


def erd():
    width, height = 1680, 1120
    image = Image.new("RGB", (width, height), BG)
    draw = ImageDraw.Draw(image)
    draw.text((40, 46), "ERD - HỆ THỐNG QUẢN LÝ SINH VIÊN - LỚP HỌC", font=font(38, True), fill=NAVY, anchor="lm")

    box_w, box_h = 470, 380
    cols = [40, 605, 1170]
    top_row, bottom_row = 110, 610

    entity(draw, cols[0], top_row, box_w, box_h, "classes", [
        ("PK", "id"),
        ("UQ", "class_code"),
        ("", "class_name"),
        ("", "room (nullable)"),
        ("", "timestamps"),
    ])
    entity(draw, cols[1], top_row, box_w, box_h, "users", [
        ("PK", "id"),
        ("", "name"),
        ("UQ", "email"),
        ("", "password (hash)"),
        ("", "role: admin | student"),
        ("FK", "class_id (nullable)"),
        ("", "avatar (nullable)"),
        ("", "timestamps"),
    ])
    entity(draw, cols[2], top_row, box_w, box_h, "student_subject", [
        ("PK", "id"),
        ("FK", "user_id"),
        ("FK", "subject_id"),
        ("UQ", "(user_id, subject_id)"),
        ("", "timestamps"),
    ])
    entity(draw, cols[0], bottom_row, box_w, box_h, "attendances", [
        ("PK", "id"),
        ("FK", "user_id"),
        ("FK", "subject_id"),
        ("", "date"),
        ("", "status: present | absent"),
        ("UQ", "(user_id, subject_id, date)"),
        ("", "timestamps"),
    ])
    entity(draw, cols[1], bottom_row, box_w, box_h, "grades", [
        ("PK", "id"),
        ("FK", "user_id"),
        ("FK", "subject_id"),
        ("", "score (0-10)"),
        ("UQ", "(user_id, subject_id)"),
        ("", "timestamps"),
    ])
    entity(draw, cols[2], bottom_row, box_w, box_h, "subjects", [
        ("PK", "id"),
        ("UQ", "code"),
        ("", "name"),
        ("", "credits"),
        ("", "timestamps"),
    ])

    y_link = top_row + 90
    connector(draw, (cols[0] + box_w, y_link), (cols[1], y_link), "1", "N")
    connector(draw, (cols[1] + box_w, y_link), (cols[2], y_link), "1", "N")

    for column in cols:
        x_link = column + 235
        first, second = (top_row + box_h, bottom_row) if column != cols[2] else (bottom_row, top_row + box_h)
        connector(draw, (x_link, first), (x_link, second), "1", "N")

    y_link = bottom_row + 190
    connector(draw, (cols[2], y_link), (cols[1] + box_w, y_link), "1", "N")

    gutter = bottom_row + box_h + 28
    route(draw, [(cols[2] + 340, bottom_row + box_h), (cols[2] + 340, gutter), (cols[0] + 130, gutter),
                 (cols[0] + 130, bottom_row + box_h)], "1", "N")

    draw.text((40, height - 40), "PK: khóa chính    FK: khóa ngoại    UQ: ràng buộc duy nhất    "
                                 "avatar: đường dẫn ảnh trên disk public", font=font(19), fill=MUTED, anchor="lm")
    image.save("erd.png")


# ---------------------------------------------------------------- Use Case

def dashed_line(draw, start, end, fill, dash=10, width=2):
    length = math.dist(start, end)
    steps = max(int(length // dash), 1)
    for i in range(steps):
        if i % 2:
            continue
        x1 = start[0] + (end[0] - start[0]) * i / steps
        y1 = start[1] + (end[1] - start[1]) * i / steps
        x2 = start[0] + (end[0] - start[0]) * (i + 1) / steps
        y2 = start[1] + (end[1] - start[1]) * (i + 1) / steps
        draw.line([(x1, y1), (x2, y2)], fill=fill, width=width)


def dashed_ellipse(draw, cx, cy, rx, ry, fill, width=2):
    steps = 160
    for i in range(steps):
        if (i // 5) % 2:
            continue
        a1 = 2 * math.pi * i / steps
        a2 = 2 * math.pi * (i + 5) / steps
        draw.line([(cx + rx * math.cos(a1), cy + ry * math.sin(a1)),
                   (cx + rx * math.cos(a2), cy + ry * math.sin(a2))], fill=fill, width=width)


def use_case(draw, cx, cy, rx, ry, label, color, dashed=False):
    if dashed:
        dashed_ellipse(draw, cx, cy, rx, ry, color)
    else:
        draw.ellipse([cx - rx, cy - ry, cx + rx, cy + ry], fill=WHITE, outline=color, width=3)
    centered(draw, cx, cy, label, 19, TEXT)


def stick_figure(draw, cx, cy, name):
    draw.ellipse([cx - 22, cy - 78, cx + 22, cy - 34], outline=NAVY, width=3)
    draw.line([(cx, cy - 34), (cx, cy + 22)], fill=NAVY, width=3)
    draw.line([(cx - 34, cy - 14), (cx + 34, cy - 14)], fill=NAVY, width=3)
    draw.line([(cx, cy + 22), (cx - 26, cy + 66)], fill=NAVY, width=3)
    draw.line([(cx, cy + 22), (cx + 26, cy + 66)], fill=NAVY, width=3)
    centered(draw, cx, cy + 92, name, 21, NAVY, bold=True)


def associate(draw, actor_x, actor_y, rail_rows, boundary_x):
    """Duong noi tac nhan den tung use case qua duong ray nam doc ben trai."""
def associate(draw, actor_x, actor_y, rows, boundary_x):
    bus_x = boundary_x - 40
    rails = sorted({cy - ry - 26 for cx, cy, rx, ry in rows})
    top, bottom = min(rails + [actor_y]), max(rails + [actor_y])
    draw.line([(actor_x, actor_y), (bus_x, actor_y)], fill=RAIL, width=2)
    draw.line([(bus_x, top), (bus_x, bottom)], fill=RAIL, width=2)
    for cx, cy, rx, ry in rows:
        rail_y = cy - ry - 26
        draw.line([(bus_x, rail_y), (cx, rail_y)], fill=RAIL, width=2)
        draw.line([(cx, rail_y), (cx, cy - ry)], fill=RAIL, width=2)


def dependency(draw, frm, to, label, at=None):
    dashed_line(draw, frm, to, MUTED)
    centered(draw, *(at or ((frm[0] + to[0]) / 2, (frm[1] + to[1]) / 2 - 12)), f"<<{label}>>", 16, MUTED, bold=True)


def usecase_diagram():
    width, height = 1680, 1370
    image = Image.new("RGB", (width, height), BG)
    draw = ImageDraw.Draw(image)
    draw.text((40, 46), "UML USE CASE - HỆ THỐNG QUẢN LÝ SINH VIÊN - LỚP HỌC", font=font(38, True), fill=NAVY,
              anchor="lm")

    left, right, top, bottom = 350, 1640, 96, 1330
    draw.rounded_rectangle([left, top, right, bottom], radius=14, fill=(252, 253, 255), outline=BLUE, width=3)
    draw.text((left + 30, top + 34), "Website quản lý sinh viên - lớp học", font=font(23, True), fill=NAVY, anchor="lm")

    def section(y, title, color):
        draw.text((left + 30, y), title, font=font(19, True), fill=color, anchor="lm")
        draw.line([(left + 30, y + 18), (right - 30, y + 18)], fill=(214, 224, 234), width=2)

    rx, ry = 165, 44
    columns = [560, 960, 1360]

    section(168, "TÁC VỤ QUẢN TRỊ", BLUE)
    admin_rows = [(cx, 280, rx, ry) for cx in columns] + [(cx, 410, rx, ry) for cx in columns]
    admin_labels = ["Đăng nhập / đăng xuất", "Quản lý lớp học", "Quản lý môn học",
                    "Quản lý sinh viên", "Quản lý điểm", "Điểm danh, xem báo cáo"]
    for (cx, cy, _, _), label in zip(admin_rows, admin_labels):
        use_case(draw, cx, cy, rx, ry, label, BLUE)

    ext_rows = [(560, 560, 150, 40), (960, 560, 150, 40), (1360, 560, 150, 40)]
    ext_labels = ["Tải ảnh đại diện", "Tìm kiếm AJAX", "Xuất CSV báo cáo"]
    for (cx, cy, erx, ery), label in zip(ext_rows, ext_labels):
        use_case(draw, cx, cy, erx, ery, label, BLUE, dashed=True)

    dependency(draw, (560, 454), (560, 520), "include", at=(478, 487))
    dependency(draw, (662, 441), (902, 528), "include", at=(782, 460))
    dependency(draw, (1062, 441), (1302, 528), "extend", at=(1182, 460))
    dependency(draw, (1360, 454), (1360, 520), "extend", at=(1442, 487))

    stick_figure(draw, 150, 277, "Admin")
    associate(draw, 190, 277, admin_rows, left)

    section(650, "TÁC VỤ SINH VIÊN", GREEN)
    student_rows = [(cx, 755, rx, ry) for cx in columns] + [(760, 890, rx, ry)]
    student_labels = ["Xem trang chủ và thông tin lớp", "Xem môn học đang mở", "Đăng ký / hủy học phần",
                      "Xem kết quả học tập của mình"]
    for (cx, cy, _, _), label in zip(student_rows, student_labels):
        use_case(draw, cx, cy, rx, ry, label, GREEN)
    stick_figure(draw, 150, 756, "Sinh viên")
    associate(draw, 190, 756, student_rows, left)

    section(965, "TÁC VỤ KHÁCH CHƯA ĐĂNG NHẬP", AMBER)
    guest_rows = [(760, 1075, 185, 44), (1180, 1075, 185, 44)]
    guest_labels = ["Quên mật khẩu, yêu cầu OTP", "Nhập OTP và đặt mật khẩu mới"]
    for (cx, cy, grx, gry), label in zip(guest_rows, guest_labels):
        use_case(draw, cx, cy, grx, gry, label, AMBER)
    stick_figure(draw, 150, 1075, "Khách")
    associate(draw, 190, 1075, guest_rows, left)

    draw.text((left + 30, 1175), "Ghi chú hiện thực", font=font(19, True), fill=NAVY, anchor="lm")
    notes = [
        "Nét liền: tác nhân được phép thực hiện use case theo vai trò do middleware checkRole kiểm tra.",
        "Nét đứt <<include>> / <<extend>>: use case mở rộng phụ thuộc vào use case cha ở nhóm quản trị.",
        "Tải ảnh đại diện lưu trên disk public; tìm kiếm AJAX trả fragment HTML + phân trang dạng JSON;",
        "OTP gửi qua dịch vụ Nodemailer (Gmail SMTP); xuất báo cáo dùng file CSV UTF-8 BOM.",
    ]
    y = 1209
    for note in notes:
        draw.text((left + 30, y), note, font=font(19), fill=TEXT, anchor="lm")
        y += 32

    image.save("usecase.png")


if __name__ == "__main__":
    erd()
    usecase_diagram()
    print("da ve erd.png va usecase.png")
