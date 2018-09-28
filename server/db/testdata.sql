BEGIN TRANSACTION;
INSERT INTO `groups` VALUES ('testgruppe','Testgruppe','Dies ist eine Testgruppe',NULL);
INSERT INTO `groups` VALUES ('js-aurich','JavaScript User Group Aurich','Noch eine Testgruppe',NULL);
INSERT INTO `group_members` VALUES ('testgruppe','admin','admin',NULL);
INSERT INTO `group_members` VALUES ('testgruppe','lea','member',NULL);
COMMIT;
