USE `meeting_booking_db`;
SET foreign_key_checks = 0;
TRUNCATE TABLE `departments`;
INSERT INTO `departments` (`id`, `department_name`) VALUES 
(1, 'สำนักปลัด (อบต.เวียง)'), 
(2, 'กองคลัง (อบต.เวียง)'), 
(3, 'กองช่าง (อบต.เวียง)'), 
(4, 'กองการศึกษา ศาสนา และวัฒนธรรม (อบต.เวียง)'), 
(5, 'กองสวัสดิการสังคม (อบต.เวียง)'), 
(6, 'กองสาธารณสุขและสิ่งแวดล้อม (อบต.เวียง)'), 
(7, 'หน่วยราชการภายนอก / ประชาชนทั่วไป');
SET foreign_key_checks = 1;
