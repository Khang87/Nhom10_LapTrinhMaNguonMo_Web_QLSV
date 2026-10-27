<?php

/**
 * Hồ sơ sinh viên demo. Bộ trường lấy đúng những gì MaNguon thu thập trên tờ lý lịch trích ngang
 * (giấy tờ tùy thân, nơi sinh, hộ khẩu thường trú, chỗ ở liên hệ, dân tộc - tôn giáo - quốc tịch,
 * khu vực và diện ưu tiên tuyển sinh), một số dòng chép nguyên văn hồ sơ thật của họ.
 *
 * Mỗi dòng là một danh sách theo thứ tự:
 * [họ tên, giới tính, ngày sinh, quê quán, mã lớp, dân tộc, tôn giáo, quốc tịch, khu vực,
 *  diện ưu tiên, số CCCD, ngày cấp CCCD, nơi cấp CCCD, nơi sinh, hộ khẩu thường trú,
 *  chỗ ở hiện nay, hình thức đào tạo]
 *
 * Ba dòng cuối để trống có chủ đích: màn hình hồ sơ còn phải hiển thị được trạng thái "chưa cập nhật".
 */

return [
    ['Nguyễn Minh Anh', 'MALE', '2006-01-15', 'Hà Nội', 'CN24A', 'Kinh', 'Không', 'Việt Nam', 'KV3', 'Không', '001206003417', '2021-04-08', 'Hà Nội', 'Quận Đống Đa, Hà Nội', 'Số 18 phố Chùa Bộc, phường Trung Liệt, quận Đống Đa, Hà Nội', 'Số 18 phố Chùa Bộc, phường Trung Liệt, quận Đống Đa, Hà Nội', 'Chính quy'],
    ['Trần Hoàng Nam', 'MALE', '2006-03-22', 'Nghệ An', 'CN24A', 'Kinh', 'Công giáo', 'Việt Nam', 'KV2-NT', 'Ưu tiên 2', '040206018394', '2021-06-15', 'Nghệ An', 'Thành phố Vinh, Nghệ An', 'Xóm 4, xã Nghi Phú, thành phố Vinh, Nghệ An', 'Ký túc xá B1, cơ sở 1, TP. Hồ Chí Minh', 'Chính quy'],
    ['Lê Thu Thảo', 'FEMALE', '2006-02-09', 'Đà Nẵng', 'CN24A', 'Kinh', 'Phật giáo', 'Việt Nam', 'KV2', 'Không', '048206007221', '2021-02-26', 'Đà Nẵng', 'Quận Hải Châu, Đà Nẵng', '68 Lê Lợi, quận Hải Châu, Đà Nẵng', '68 Lê Lợi, quận Hải Châu, Đà Nẵng', 'Chính quy'],
    ['Phạm Quốc Bảo', 'MALE', '2005-11-30', 'Cần Thơ', 'CN24A', 'Kinh', 'Không', 'Việt Nam', 'KV2', 'Không', '086205011980', '2020-12-11', 'Cần Thơ', 'Quận Ninh Kiều, Cần Thơ', '120 Mậu Thân, quận Ninh Kiều, Cần Thơ', '120 Mậu Thân, quận Ninh Kiều, Cần Thơ', 'Chính quy'],
    ['Bùi Quốc Hùng', 'MALE', '2005-03-04', 'Cà Mau', 'CN24A', 'Kinh', 'Không', 'Việt Nam', 'KV2', 'Không', '095205003219', '2021-08-27', 'Cà Mau', 'Cà Mau', 'ấp 14 Xóm Lung, xã Vĩnh Mỹ, Cà Mau', '140 Lê Trọng Tấn, phường Tây Thạnh, quận Tân Phú, TP. Hồ Chí Minh', 'Chính quy'],
    ['Võ Ngọc Linh', 'FEMALE', '2006-07-18', 'Huế', 'CN24A', 'Kinh', 'Không', 'Việt Nam', 'KV2-NT', 'Con thương binh', '048206013477', '2021-05-20', 'Thừa Thiên Huế', 'Thành phố Huế', '25 Nguyễn Huệ, thành phố Huế', '25 Nguyễn Huệ, thành phố Huế', 'Chính quy'],
    ['Đặng Tiến Dũng', 'MALE', '2005-09-02', 'Nha Trang', 'CN24B', 'Kinh', 'Không', 'Việt Nam', 'KV2', 'Không', '056205009812', '2020-11-06', 'Khánh Hòa', 'Thành phố Nha Trang', '77 Biệt thự, phường Lộc Thọ, Nha Trang', '77 Biệt thự, phường Lộc Thọ, Nha Trang', 'Chính quy'],
    ['Đỗ Hà My', 'FEMALE', '2006-05-27', 'Hải Phòng', 'CN24B', 'Hoa', 'Tin Lành', 'Việt Nam', 'KV3', 'Không', '031206004568', '2021-07-19', 'Hải Phòng', 'Quận Lê Chân, Hải Phòng', 'Số 5 Trần Nguyên Hãn, quận Lê Chân, Hải Phòng', 'Số 5 Trần Nguyên Hãn, quận Lê Chân, Hải Phòng', 'Chính quy'],
    ['Hồ Khánh Vy', 'FEMALE', '2006-12-01', 'Đà Lạt', 'CN24B', 'Kinh', 'Phật giáo', 'Việt Nam', 'KV1', 'Ưu tiên 3', '068206002231', '2021-09-14', 'Lâm Đồng', 'Thành phố Đà Lạt', '199 Trần Hưng Đạo, thành phố Đà Lạt', 'Ký túc xá B2, cơ sở 1, TP. Hồ Chí Minh', 'Chính quy'],
    ['Ngô Văn Tú', 'MALE', '2005-06-13', 'Bắc Ninh', 'CN24B', 'Kinh', 'Không', 'Việt Nam', 'KV2-NT', 'Không', '027205016740', '2020-10-23', 'Bắc Ninh', 'Thành phố Bắc Ninh', 'Thôn Tam Á, xã Gia Đông, thành phố Bắc Ninh', 'Thôn Tam Á, xã Gia Đông, thành phố Bắc Ninh', 'Vừa làm vừa học'],
    ['Dương Thị Yến', 'FEMALE', '2006-08-25', 'Kiên Giang', 'CN24B', 'Khmer', 'Hòa Hảo', 'Việt Nam', 'KV1', 'Hộ nghèo', '089206011095', '2021-03-30', 'Kiên Giang', 'Thành phố Rạch Giá', '89 Lê Lợi, thành phố Rạch Giá', '89 Lê Lợi, thành phố Rạch Giá', 'Chính quy'],
    ['Lý Thanh Tùng', 'MALE', '2005-04-07', 'Bình Dương', 'CN24B', 'Kinh', 'Không', 'Việt Nam', 'KV2', 'Không', '074205008123', '2020-09-18', 'Bình Dương', 'Thành phố Thủ Dầu Một', '12 đại lộ Bình Dương, thành phố Thủ Dầu Một', '12 đại lộ Bình Dương, thành phố Thủ Dầu Một', 'Chính quy'],
    ['Trịnh Hoàng Phúc', 'MALE', '2006-10-19', 'Vũng Tàu', 'HS24A', 'Kinh', 'Công giáo', 'Việt Nam', 'KV2', 'Không', '077206005341', '2021-08-02', 'Bà Rịa - Vũng Tàu', 'Thành phố Vũng Tàu', '45 Trần Phú, thành phố Vũng Tàu', '45 Trần Phú, thành phố Vũng Tàu', 'Chính quy'],
    ['Mai Thảo My', 'FEMALE', '2006-01-08', 'Hà Nội', 'HS24A', 'Tày', 'Không', 'Việt Nam', 'KV1', 'Ưu tiên 2', '001206017782', '2021-01-25', 'Hà Nội', 'Huyện Ba Vì, Hà Nội', 'Thôn Sinh, xã Minh Quang, huyện Ba Vì, Hà Nội', 'Số 33 Nguyễn Khang, quận Cầu Giấy, Hà Nội', 'Chính quy'],
    ['Phan Văn Đạt', 'MALE', '2005-05-16', 'Nghệ An', 'HS24A', 'Kinh', 'Không', 'Việt Nam', 'KV2-NT', 'Không', '040205012260', '2020-12-30', 'Nghệ An', 'Huyện Diễn Châu', 'Xã Diễn Kỷ, huyện Diễn Châu, Nghệ An', 'Xã Diễn Kỷ, huyện Diễn Châu, Nghệ An', 'Chính quy'],
    ['Trương Thị Hoa', 'FEMALE', '2006-09-28', 'Đắk Lắk', 'HS24A', 'Ê Đê', 'Tin Lành', 'Việt Nam', 'KV1', 'Hộ nghèo', '066206009473', '2021-06-08', 'Đắk Lắk', 'Thành phố Buôn Ma Thuột', '220 Lê Duẩn, thành phố Buôn Ma Thuột', '220 Lê Duẩn, thành phố Buôn Ma Thuột', 'Chính quy'],
    ['Lê Minh Khôi', 'MALE', '2005-12-11', 'Hải Phòng', 'HC24A', 'Kinh', 'Không', 'Việt Nam', 'KV3', 'Không', '031205014509', '2020-11-17', 'Hải Phòng', 'Quận Ngô Quyền, Hải Phòng', '185 Lạch Tray, quận Ngô Quyền, Hải Phòng', '185 Lạch Tray, quận Ngô Quyền, Hải Phòng', 'Chính quy'],
    ['Nguyễn Nhật Lan', 'FEMALE', '2006-06-04', 'Đồng Nai', 'HC24A', 'Kinh', 'Phật giáo', 'Việt Nam', 'KV2-NT', 'Ưu tiên 3', '072206006718', '2021-04-29', 'Đồng Nai', 'Thành phố Biên Hòa', 'Khu phố 2, phường Trảng Dài, thành phố Biên Hòa', 'Khu phố 2, phường Trảng Dài, thành phố Biên Hòa', 'Chính quy'],
    ['Trần Quang Huy', 'MALE', '2005-08-21', 'Huế', 'HC24A', 'Kinh', 'Không', 'Việt Nam', 'KV2', 'Không', '048205010362', '2020-10-05', 'Thừa Thiên Huế', 'Thị xã Hương Thủy', '15 Điện Biên Phủ, thị xã Hương Thủy', '15 Điện Biên Phủ, thị xã Hương Thủy', 'Chính quy'],
    ['Phạm Thanh Tâm', 'FEMALE', '2006-02-14', 'Nha Trang', 'HC24A', 'Kinh', 'Công giáo', 'Việt Nam', 'KV2', 'Không', '056206003187', '2021-02-11', 'Khánh Hòa', 'Thành phố Nha Trang', '66 Yersin, thành phố Nha Trang', '66 Yersin, thành phố Nha Trang', 'Chính quy'],
    ['Hoàng Bảo Ngọc', 'FEMALE', '2006-04-30', 'Hà Nội', 'AT24A', 'Kinh', 'Không', 'Việt Nam', 'KV3', 'Không', '001206008845', '2021-05-07', 'Hà Nội', 'Quận Hà Đông, Hà Nội', 'Chung cư CT2, yên Nghĩa, quận Hà Đông, Hà Nội', 'Chung cư CT2, Yên Nghĩa, quận Hà Đông, Hà Nội', 'Chính quy'],
    ['Vũ Tiến Lực', 'MALE', '2005-10-03', 'Thanh Hoá', 'AT24A', 'Mường', 'Không', 'Việt Nam', 'KV1', 'Ưu tiên 2', '038205019204', '2020-12-21', 'Thanh Hoá', 'Huyện Thọ Xuân', 'Thôn 4, xã Xuân Phú, huyện Thọ Xuân', 'Thôn 4, xã Xuân Phú, huyện Thọ Xuân', 'Chính quy'],
    ['Đoàn Thị Mai Anh', 'FEMALE', '2006-07-26', 'Đà Nẵng', 'AT24A', 'Kinh', 'Không', 'Việt Nam', 'KV2', 'Không', '048206015530', '2021-08-16', 'Đà Nẵng', 'Quận Sơn Trà, Đà Nẵng', '98 Nguyễn Văn Thoại, quận Sơn Trà, Đà Nẵng', '98 Nguyễn Văn Thoại, quận Sơn Trà, Đà Nẵng', 'Chính quy'],
    // Nghỉ học giữa chừng: vẫn còn lớp hành chính để danh sách sinh viên không bị mất dấu em này.
    ['Ngô Gia Hỷ', 'MALE', '2005-02-19', 'Cần Thơ', 'AT24A', 'Hoa', 'Không', 'Việt Nam', 'KV2-NT', 'Không', '086205007416', '2020-09-09', 'Cần Thơ', 'Quận Bình Thủy, Cần Thơ', '210 Nguyễn Văn Linh, quận Bình Thủy, Cần Thơ', '210 Nguyễn Văn Linh, quận Bình Thủy, Cần Thơ', 'Chính quy'],
    // Chưa xếp lớp và tốt nghiệp: hai trạng thái mà bộ lọc danh sách sinh viên phải còn đường hiển thị.
    ['Nguyễn Kim Ngân', 'FEMALE', '2004-12-05', 'TP. Hồ Chí Minh', null, 'Kinh', 'Không', 'Việt Nam', 'KV3', 'Không', '079204026178', '2020-08-12', 'TP. Hồ Chí Minh', 'Quận 3, TP. Hồ Chí Minh', '77 Võ Văn Tần, quận 3, TP. Hồ Chí Minh', '77 Võ Văn Tần, quận 3, TP. Hồ Chí Minh', 'Chính quy'],
    // Hồ sơ còn thiếu: ngày sinh, giấy tờ và địa chỉ để trống có chủ đích.
    ['Thạch Hoàng Long', 'MALE', null, 'Sóc Trăng', 'MM24A', 'Khmer', 'Phật giáo', 'Việt Nam', 'KV1', null, null, null, null, null, null, null, 'Liên danh đào tạo'],
    ['Lưu Bạch Yến', 'FEMALE', null, 'An Giang', 'MM24A', 'Chăm', 'Hồi giáo', 'Việt Nam', 'KV1', null, null, null, null, null, null, null, null],
    ['Đặng Lê Khôi', 'MALE', null, 'TP. Hồ Chí Minh', 'MM24A', 'Kinh', 'Không', 'Việt Nam', 'KV3', null, null, null, null, null, null, null, null],
];
