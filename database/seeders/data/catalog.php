<?php

/**
 * Danh mục lấy từ hệ thống quản lý đào tạo đang chạy thật (dự án MaNguon). Cỡ dữ liệu — 8 khoa,
 * 5 chuyên ngành, 26 môn với tín chỉ 2..10, 25 giảng viên kèm học vị, khung chương trình 8 kỳ —
 * đọc nguyên văn từ database của họ rồi xuất ra file tĩnh trong repo, vì seeder demo không được
 * phụ thuộc một đường dẫn nằm ngoài dự án. Tên lớp, tên người và số điện thoại là dữ liệu mẫu.
 */

return [
    /** Khoa: 8 đơn vị mà giảng viên được phân công, liên hệ theo mẫu của khoa CNTT. */
    'departments' => [
        ['code' => 'CNTT', 'name' => 'Khoa Công nghệ Thông tin', 'phone' => '028.38161673', 'email' => 'cntt@huit.edu.vn', 'office' => 'Phòng B.302, Tòa nhà B, Trường ĐH Công Thương TP.HCM', 'description' => 'Khoa đào tạo các chuyên ngành thuộc lĩnh vực CNTT, kỹ thuật phần mềm, hệ thống thông tin, an toàn thông tin và khoa học máy tính.'],
        ['code' => 'QTKD', 'name' => 'Khoa Quản trị Kinh doanh', 'phone' => '028.38161684', 'email' => 'qtkd@huit.edu.vn', 'office' => 'Khu A, Phòng 101, Cơ sở 1', 'description' => 'Đào tạo quản trị kinh doanh, marketing và thương mại điện tử cho doanh nghiệp số.'],
        ['code' => 'NN', 'name' => 'Khoa Ngoại ngữ', 'phone' => '028.38161695', 'email' => 'nn@huit.edu.vn', 'office' => 'Khu A, Phòng 202, Cơ sở 1', 'description' => 'Giảng dạy ngoại ngữ, tiếng Anh thương mại và năng lực giao tiếp cho toàn trường.'],
        ['code' => 'TCNH', 'name' => 'Khoa Tài chính - Ngân hàng', 'phone' => '028.38161706', 'email' => 'tcnh@huit.edu.vn', 'office' => 'Khu A, Phòng 303, Cơ sở 1', 'description' => 'Đào tạo tài chính doanh nghiệp, ngân hàng và công nghệ tài chính (Fintech).'],
        ['code' => 'CNTP', 'name' => 'Khoa Công nghệ Thực phẩm', 'phone' => '028.38161717', 'email' => 'cntp@huit.edu.vn', 'office' => 'Khu A, Phòng 404, Cơ sở 1', 'description' => 'Đào tạo công nghệ chế biến, kiểm nghiệm và an toàn thực phẩm.'],
        ['code' => 'KDDT', 'name' => 'Khoa Kỹ thuật Điện - Điện tử', 'phone' => '028.38161728', 'email' => 'kddt@huit.edu.vn', 'office' => 'Khu A, Phòng 505, Cơ sở 1', 'description' => 'Đào tạo kỹ thuật điện, điện tử, hệ thống nhúng và tự động hóa.'],
        ['code' => 'LUAT', 'name' => 'Khoa Luật', 'phone' => '028.38161739', 'email' => 'luat@huit.edu.vn', 'office' => 'Khu A, Phòng 606, Cơ sở 1', 'description' => 'Đào tạo luật kinh tế, sở hữu trí tuệ và pháp lý công nghệ.'],
        ['code' => 'KHUD', 'name' => 'Khoa Khoa học Ứng dụng', 'phone' => '028.38161750', 'email' => 'khud@huit.edu.vn', 'office' => 'Khu A, Phòng 707, Cơ sở 1', 'description' => 'Giảng dạy toán, tin học ứng dụng và khoa học cơ bản cho mọi chuyên ngành.'],
    ],

    /** Chuyên ngành kèm mô tả chương trình đào tạo. */
    'majors' => [
        ['code' => 'CNPM', 'name' => 'Công nghệ phần mềm', 'department' => 'CNTT', 'description' => 'Đào tạo kỹ sư phần mềm, kiến trúc hệ thống, phát triển ứng dụng Web/Mobile và quy trình kiểm thử hiện đại.'],
        ['code' => 'HTTT', 'name' => 'Hệ thống thông tin', 'department' => 'CNTT', 'description' => 'Đào tạo phân tích thiết kế hệ thống, cơ sở dữ liệu lớn (Big Data), ERP và giải pháp số doanh nghiệp.'],
        ['code' => 'KHMT', 'name' => 'Khoa học máy tính', 'department' => 'CNTT', 'description' => 'Đào tạo chuyên sâu về thuật toán, Trí tuệ nhân tạo (AI), Học máy (Machine Learning) và Xử lý dữ liệu lớn.'],
        ['code' => 'ATTT', 'name' => 'An toàn thông tin', 'department' => 'CNTT', 'description' => 'Đào tạo an ninh mạng, mật mã học ứng dụng, kiểm thử xâm nhập (PenTest) và phòng thủ hệ thống mạng.'],
        ['code' => 'MMT', 'name' => 'Mạng máy tính & Truyền thông', 'department' => 'CNTT', 'description' => 'Đào tạo quản trị hạ tầng mạng, Điện toán đám mây (Cloud Computing - AWS/Azure/GCP) và thiết bị viễn thông.'],
    ],

    /** Môn học: major null là môn chung của toàn trường. */
    'subjects' => [
        ['code' => 'OPEN101', 'name' => 'Lập trình mã nguồn mở', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Cộng tác qua Git, giấy phép mã nguồn mở và đóng góp vào dự án thật.'],
        ['code' => 'IOT101', 'name' => 'Internet of Things', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Cảm biến, vi điều khiển ESP32, giao thức MQTT và kết nối thiết bị.'],
        ['code' => 'NOSQL101', 'name' => 'Dữ liệu NoSQL', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Mô hình tài liệu, Redis, MongoDB và khi nào nên bỏ bảng quan hệ.'],
        ['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Xây dựng ứng dụng web với PHP, Laravel, Bootstrap và bảo mật form.'],
        ['code' => 'DB101', 'name' => 'Cơ sở dữ liệu', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Mô hình quan hệ, chuẩn hóa, truy vấn SQL và điều khiển giao dịch.'],
        ['code' => 'NET101', 'name' => 'Mạng máy tính', 'credits' => 2, 'major' => null, 'sessions' => 30, 'description' => 'Mô hình TCP/IP, định tuyến, chuyển mạch và thiết kế mạng LAN/WAN.'],
        ['code' => 'DS101', 'name' => 'Cấu trúc dữ liệu và giải thuật', 'credits' => 4, 'major' => null, 'sessions' => 60, 'description' => 'Danh sách, cây, đồ thị, thuật toán sắp xếp và phân tích độ phức tạp.'],
        ['code' => 'SE101', 'name' => 'Nhập môn Công nghệ phần mềm', 'credits' => 3, 'major' => 'CNPM', 'sessions' => 45, 'description' => 'Quy trình phát triển phần mềm, đặc tả yêu cầu và mô hình hóa UML.'],
        ['code' => 'AI101', 'name' => 'Trí tuệ nhân tạo', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Tìm kiếm, biểu diễn tri thức, heuristic và mở đầu học máy.'],
        ['code' => 'MOB101', 'name' => 'Lập trình thiết bị di động', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Ứng dụng Android và iOS theo mô hình MVC, lưu trữ dữ liệu cục bộ.'],
        ['code' => 'SEC101', 'name' => 'An toàn thông tin', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Mật mã, các tấn công thường gặp, ứng cứu sự cố và quản trị an toàn.'],
        ['code' => 'GRAD101', 'name' => 'Khóa luận tốt nghiệp', 'credits' => 10, 'major' => null, 'sessions' => 150, 'description' => 'Nghiên cứu tốt nghiệp: chọn đề tài, phương pháp luận và bảo vệ trước hội đồng.'],
        ['code' => 'ENG101', 'name' => 'Tiếng Anh chuyên ngành CNTT', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Thuật ngữ CNTT bằng tiếng Anh, đọc hiểu tài liệu và trình bày kỹ thuật.'],
        ['code' => 'LAW101', 'name' => 'Pháp luật đại cương & Luật CNTT', 'credits' => 2, 'major' => null, 'sessions' => 30, 'description' => 'Nhà nước và pháp luật, sở hữu trí tuệ và quy định về an ninh mạng.'],
        ['code' => 'MKT101', 'name' => 'Marketing căn bản & Digital Marketing', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Marketing căn bản chuyển sang Digital Marketing: SEO, quảng cáo và thương hiệu số.'],
        ['code' => 'MATH101', 'name' => 'Toán rời rạc & Đại số tuyến tính', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Logic mệnh đề, quan hệ, đồ thị, ma trận và không gian vector.'],
        ['code' => 'CNPM102', 'name' => 'Kiểm thử phần mềm và Đảm bảo chất lượng', 'credits' => 3, 'major' => 'CNPM', 'sessions' => 45, 'description' => 'Kiểm thử hộp đen - hộp trắng, tự động hóa test và quy trình QA.'],
        ['code' => 'CNPM103', 'name' => 'Kiến trúc phần mềm và Thiết kế mẫu', 'credits' => 3, 'major' => 'CNPM', 'sessions' => 45, 'description' => 'Kiến trúc layered, vi dịch vụ, thiết kế mẫu và nguyên tắc SOLID.'],
        ['code' => 'HTTT101', 'name' => 'Phân tích & Thiết kế hệ thống thông tin', 'credits' => 3, 'major' => 'HTTT', 'sessions' => 45, 'description' => 'Phân tích nghiệp vụ, thiết kế hệ thống thông tin và quản trị dự án CNTT.'],
        ['code' => 'HTTT102', 'name' => 'Quản trị hệ cơ sở dữ liệu doanh nghiệp', 'credits' => 3, 'major' => 'HTTT', 'sessions' => 45, 'description' => 'Vận hành hệ quản trị CSDL doanh nghiệp: sao lưu, phân quyền, tối ưu câu lệnh.'],
        ['code' => 'KHMT101', 'name' => 'Học máy ứng dụng (Applied Machine Learning)', 'credits' => 4, 'major' => 'KHMT', 'sessions' => 60, 'description' => 'Hồi quy, cây quyết định, mạng nơ-ron và đánh giá chất lượng mô hình.'],
        ['code' => 'KHMT102', 'name' => 'Xử lý ngôn ngữ tự nhiên và Thị giác máy tính', 'credits' => 3, 'major' => 'KHMT', 'sessions' => 45, 'description' => 'Token hóa văn bản, mô hình ngôn ngữ và mô hình thị giác máy tính.'],
        ['code' => 'ATTT101', 'name' => 'Mật mã học cơ sở và Ứng dụng bảo mật', 'credits' => 3, 'major' => 'ATTT', 'sessions' => 45, 'description' => 'Mã hóa đối xứng - bất đối xứng, chữ ký số và hạ tầng khóa công khai.'],
        ['code' => 'ATTT102', 'name' => 'Kỹ thuật tấn công và Phòng thủ mạng', 'credits' => 3, 'major' => 'ATTT', 'sessions' => 45, 'description' => 'Khai thác lỗ hổng, kiểm thử xâm nhập, phòng thủ mạng và phân tích mã độc.'],
        ['code' => 'MMT101', 'name' => 'Quản trị hệ thống Linux & Windows Server', 'credits' => 3, 'major' => null, 'sessions' => 45, 'description' => 'Quản trị Linux và Windows Server: dịch vụ, người dùng, nhóm và phân quyền.'],
        ['code' => 'MMT102', 'name' => 'Điện toán đám mây và DevOps', 'credits' => 3, 'major' => 'MMT', 'sessions' => 45, 'description' => 'Ảo hóa, container, pipeline CI/CD và vận hành hệ thống trên cloud.'],
    ],

    /** Giảng viên: degree là học vị + chuyên môn, dùng cho cột Học vị. */
    'lecturers' => [
        ['code' => 'GV001', 'name' => 'Nguyễn Văn An', 'gender' => 'MALE', 'degree' => 'Thạc sĩ Khoa học Máy tính', 'department' => 'CNTT', 'phone' => '0912345671', 'email' => 'annv@huit.edu.vn'],
        ['code' => 'GV002', 'name' => 'Lê Hoàng Nam', 'gender' => 'MALE', 'degree' => 'Tiến sĩ Kỹ thuật Phần mềm', 'department' => 'CNTT', 'phone' => '0912345672', 'email' => 'namlh@huit.edu.vn'],
        ['code' => 'GV003', 'name' => 'Trần Thị Bích', 'gender' => 'FEMALE', 'degree' => 'Thạc sĩ Hệ thống Thông tin', 'department' => 'CNTT', 'phone' => '0912345673', 'email' => 'bichttt@huit.edu.vn'],
        ['code' => 'GV004', 'name' => 'Phạm Minh Tuấn', 'gender' => 'MALE', 'degree' => 'Phó Giáo sư Mạng máy tính', 'department' => 'CNTT', 'phone' => '0912345674', 'email' => 'tuanpm@huit.edu.vn'],
        ['code' => 'GV005', 'name' => 'Đỗ Thị Mai', 'gender' => 'FEMALE', 'degree' => 'Thạc sĩ Trí tuệ Nhân tạo', 'department' => 'CNTT', 'phone' => '0912345675', 'email' => 'maidt@huit.edu.vn'],
        ['code' => 'GV006', 'name' => 'Vũ Quốc Huy', 'gender' => 'MALE', 'degree' => 'Tiến sĩ Cơ sở Dữ liệu', 'department' => 'CNTT', 'phone' => '0912345676', 'email' => 'huyvq@huit.edu.vn'],
        ['code' => 'GV007', 'name' => 'Nguyễn Hữu Trọng', 'gender' => 'MALE', 'degree' => 'Thạc sĩ An toàn Thông tin', 'department' => 'CNTT', 'phone' => '0912345677', 'email' => 'trongnh@huit.edu.vn'],
        ['code' => 'GV008', 'name' => 'Lê Thu Hà', 'gender' => 'FEMALE', 'degree' => 'Tiến sĩ Khoa học Dữ liệu', 'department' => 'CNTT', 'phone' => '0912345678', 'email' => 'halt@huit.edu.vn'],
        ['code' => 'GV009', 'name' => 'Bùi Anh Tuấn', 'gender' => 'MALE', 'degree' => 'Tiến sĩ Kiến trúc Phần mềm', 'department' => 'CNTT', 'phone' => '0912345679', 'email' => 'tuanba@huit.edu.vn'],
        ['code' => 'GV010', 'name' => 'Phan Bích Ngọc', 'gender' => 'FEMALE', 'degree' => 'Thạc sĩ Giao diện & Trải nghiệm UI/UX', 'department' => 'CNTT', 'phone' => '0912345680', 'email' => 'ngocpb@huit.edu.vn'],
        ['code' => 'GV_QTR01', 'name' => 'Trần Đình Thắng', 'gender' => 'MALE', 'degree' => 'Tiến sĩ Quản trị Chiến lược', 'department' => 'QTKD', 'phone' => '0912345681', 'email' => 'thangtd@huit.edu.vn'],
        ['code' => 'GV_QTR02', 'name' => 'Nguyễn Thị Thu Trang', 'gender' => 'FEMALE', 'degree' => 'Thạc sĩ Marketing Số', 'department' => 'QTKD', 'phone' => '0912345682', 'email' => 'trangntt@huit.edu.vn'],
        ['code' => 'GV_QTR03', 'name' => 'Lê Minh Tuấn', 'gender' => 'MALE', 'degree' => 'Tiến sĩ Thương mại Điện tử', 'department' => 'QTKD', 'phone' => '0912345683', 'email' => 'tuanlm@huit.edu.vn'],
        ['code' => 'GV_NN01', 'name' => 'Jennifer Phạm (Phạm Thị Kim Loan)', 'gender' => 'FEMALE', 'degree' => 'Thạc sĩ Ngôn ngữ Anh (TESOL)', 'department' => 'NN', 'phone' => '0912345684', 'email' => 'loanpk@huit.edu.vn'],
        ['code' => 'GV_NN02', 'name' => 'David Trần (Trần Hoàng Kha)', 'gender' => 'MALE', 'degree' => 'Thạc sĩ Tiếng Anh Chuyên ngành', 'department' => 'NN', 'phone' => '0912345685', 'email' => 'khatran@huit.edu.vn'],
        ['code' => 'GV_NN03', 'name' => 'Nguyễn Văn Hậu', 'gender' => 'MALE', 'degree' => 'Tiến sĩ Ngôn ngữ học Ứng dụng', 'department' => 'NN', 'phone' => '0912345686', 'email' => 'haunv@huit.edu.vn'],
        ['code' => 'GV_TC01', 'name' => 'Vũ Đình Khoa', 'gender' => 'MALE', 'degree' => 'Phó Giáo sư Tài chính Doanh nghiệp', 'department' => 'TCNH', 'phone' => '0912345687', 'email' => 'khoavd@huit.edu.vn'],
        ['code' => 'GV_TC02', 'name' => 'Trần Mỹ Duyên', 'gender' => 'FEMALE', 'degree' => 'Tiến sĩ Fintech & Ngân hàng số', 'department' => 'TCNH', 'phone' => '0912345688', 'email' => 'duyentm@huit.edu.vn'],
        ['code' => 'GV_TP01', 'name' => 'Nguyễn Văn Thịnh', 'gender' => 'MALE', 'degree' => 'Tiến sĩ Công nghệ Sinh học Thực phẩm', 'department' => 'CNTP', 'phone' => '0912345689', 'email' => 'thinhnv@huit.edu.vn'],
        ['code' => 'GV_TP02', 'name' => 'Lê Kim Ngân', 'gender' => 'FEMALE', 'degree' => 'Thạc sĩ Đảm bảo Chất lượng & ATTP', 'department' => 'CNTP', 'phone' => '0912345690', 'email' => 'nganlk@huit.edu.vn'],
        ['code' => 'GV_DT01', 'name' => 'Huỳnh Thanh Tùng', 'gender' => 'MALE', 'degree' => 'Tiến sĩ Kỹ thuật Viễn thông & IoT', 'department' => 'KDDT', 'phone' => '0912345691', 'email' => 'tunght@huit.edu.vn'],
        ['code' => 'GV_DT02', 'name' => 'Phan Đình Trọng', 'gender' => 'MALE', 'degree' => 'Thạc sĩ Hệ thống Nhúng & Robot', 'department' => 'KDDT', 'phone' => '0912345692', 'email' => 'trongpd@huit.edu.vn'],
        ['code' => 'GV_LUAT01', 'name' => 'Trịnh Công Minh', 'gender' => 'MALE', 'degree' => 'Tiến sĩ Luật Kinh tế & Sở hữu Trí tuệ', 'department' => 'LUAT', 'phone' => '0912345693', 'email' => 'minhtc@huit.edu.vn'],
        ['code' => 'GV_KH01', 'name' => 'Dương Ngọc Mai', 'gender' => 'FEMALE', 'degree' => 'Tiến sĩ Toán Tin Ứng dụng & Giải tích', 'department' => 'KHUD', 'phone' => '0912345694', 'email' => 'maidn@huit.edu.vn'],
        ['code' => 'GV_KH02', 'name' => 'Hoàng Văn Cường', 'gender' => 'MALE', 'degree' => 'Thạc sĩ Vật lý Kỹ thuật & Bán dẫn', 'department' => 'KHUD', 'phone' => '0912345695', 'email' => 'cuonghv@huit.edu.vn'],
    ],

    /**
     * Khung chương trình: [chuyên ngành, học kỳ đào tạo, 'môn bắt buộc|môn tự chọn'].
     * Một cặp (ngành, môn) chỉ có một dòng, nên ngành nào chuyển môn giữa các kỳ thì lấy kỳ ghi sau.
     */
    'study_plans' => [
        ['CNPM', 1, 'DS101,SE101,MATH101'],
        ['CNPM', 2, 'DB101,NET101|LAW101'],
        ['CNPM', 3, 'WEB101,CNPM102|NOSQL101,MKT101'],
        ['CNPM', 4, 'ENG101,OPEN101,CNPM103|IOT101'],
        ['CNPM', 5, 'AI101|MOB101'],
        ['CNPM', 6, 'SEC101'],
        ['CNPM', 8, 'GRAD101'],
        ['HTTT', 1, 'DS101,MATH101,ENG101|LAW101'],
        ['HTTT', 2, 'DB101,NET101|MKT101'],
        ['HTTT', 3, 'HTTT101,NOSQL101|WEB101'],
        ['HTTT', 4, 'HTTT102|OPEN101'],
        ['HTTT', 5, 'AI101|IOT101'],
        ['HTTT', 6, 'SEC101'],
        ['HTTT', 8, 'GRAD101'],
        ['KHMT', 1, 'DS101,MATH101,ENG101|LAW101'],
        ['KHMT', 2, 'DB101,AI101|NET101'],
        ['KHMT', 3, 'KHMT101,NOSQL101|WEB101'],
        ['KHMT', 4, 'KHMT102|OPEN101'],
        ['KHMT', 5, 'IOT101|MOB101'],
        ['KHMT', 6, 'SEC101'],
        ['KHMT', 8, 'GRAD101'],
        ['ATTT', 1, 'DS101,MATH101,ENG101'],
        ['ATTT', 2, 'NET101,DB101|LAW101'],
        ['ATTT', 3, 'ATTT101,SEC101|WEB101'],
        ['ATTT', 4, 'ATTT102,MMT101|OPEN101'],
        ['ATTT', 5, 'AI101|NOSQL101'],
        ['ATTT', 6, '|MOB101'],
        ['ATTT', 8, 'GRAD101'],
        ['MMT', 1, 'DS101,MATH101,ENG101'],
        ['MMT', 2, 'NET101,DB101|LAW101'],
        ['MMT', 3, 'MMT101,WEB101|IOT101'],
        ['MMT', 4, 'MMT102,SEC101|OPEN101'],
        ['MMT', 5, 'AI101|NOSQL101'],
        ['MMT', 6, '|MOB101'],
        ['MMT', 8, 'GRAD101'],
    ],

    /** Môn cần học trước (do bộ môn xếp, MaNguon không lưu quan hệ này). */
    'prerequisites' => [
        'WEB101' => ['DS101'],
        'CNPM103' => ['SE101'],
        'HTTT102' => ['DB101'],
        'KHMT102' => ['KHMT101'],
        'ATTT102' => ['SEC101'],
    ],

    /** Môn học song hành: đăng ký một môn thì phải đứng tên môn kia trong cùng kỳ. */
    'concurrents' => [
        'CNPM102' => ['WEB101'],
    ],

];
