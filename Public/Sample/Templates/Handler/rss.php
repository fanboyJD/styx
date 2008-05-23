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
		<?php
			/* Still searching for a proper sample image =)
			<image>
				<url></url>
				<title></title>
				<link/>
			</image>
			*/
		?>
		<?php
			// We can do anything we want here and more like iterating through $this->data :)
		
			/* @var $db db */
			$db = db::getInstance();
			foreach($db->select('news')->order('time DESC') as $n)
				echo '<item>
					<title>'.$n['title'].'</title>
					<link>${app.link}'.$n['pagetitle'].'</link>
					<description>
						<![CDATA[
							'.substr($n['content'], 0, 200).'...
						]]>
					</description>
					<pubDate>'.date('r', $n['time']).'</pubDate>
				</item>';
		?>
	</channel>
</rss>