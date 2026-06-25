ALTER TABLE room_features ADD COLUMN total_qty INT DEFAULT 1, ADD COLUMN active_qty INT DEFAULT 1, ADD COLUMN maintenance_qty INT DEFAULT 0;
UPDATE room_features SET total_qty=12, active_qty=10, maintenance_qty=2 WHERE id=1;
UPDATE room_features SET total_qty=8, active_qty=7, maintenance_qty=1 WHERE id=2;
UPDATE room_features SET total_qty=6, active_qty=6, maintenance_qty=0 WHERE id=3;
UPDATE room_features SET total_qty=20, active_qty=18, maintenance_qty=2 WHERE id=4;
