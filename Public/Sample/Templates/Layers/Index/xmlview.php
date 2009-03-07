<?php
	foreach($this->Model as $n)
		echo '<item>
			<title>'.$n['title'].'</title>
			<link>'.$this->link($n['pagetitle']).'</link>
			<description>
				<![CDATA[
					'.Data::excerpt($n['content']).'
				]]>
			</description>
			<pubDate>'.date('r', $n['time']).'</pubDate>
		</item>';