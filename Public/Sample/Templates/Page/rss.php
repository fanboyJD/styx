<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0">
	<channel>
		<title>${app.name}</title>
		<link>${app.link}</link>
		<description>This is a sample description</description>
		<language>en-US</language>

		<pubDate><?php echo date('r'); ?></pubDate>
		<lastBuildDate><?php echo date('r'); ?></lastBuildDate>
		<generator>${app.name}</generator>
		<managingEditor>${app.mail}</managingEditor>
		<webMaster>${app.mail}</webMaster>
		<image>
			<url>${app.link}Images/styx.png</url>
			<title>${app.name}</title>
			<link/>
		</image>
		${layer}
	</channel>
</rss>