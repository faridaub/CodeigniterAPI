DROP TABLE IF EXISTS ci_sessions;
CREATE TABLE ci_sessions (
  session_id VARCHAR(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  ip_address VARCHAR(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  user_agent VARCHAR(120) COLLATE utf8_unicode_ci NOT NULL,
  last_activity INT(10) UNSIGNED NOT NULL DEFAULT 0,
  user_data TEXT COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (session_id),
  INDEX last_activity_idx (last_activity)
)ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
