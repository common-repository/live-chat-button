<?php

namespace AsanaPlugins\WhatsApp\Updates;

function update_420() {
	global $wpdb;

	$wpdb->query( "ALTER TABLE {$wpdb->prefix}asnp_ewhatsapp_ai_content_layout ADD COLUMN `tags` text NULL default NULL;" );
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}asnp_ewhatsapp_ai_content_layout ADD COLUMN `promptTags` longtext NULL default NULL;" );
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}asnp_ewhatsapp_ai_content_layout ADD COLUMN `keywords` text NULL default NULL;" );
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}asnp_ewhatsapp_ai_content_layout ADD COLUMN `excludeKeywords` text NULL default NULL;" );
}
