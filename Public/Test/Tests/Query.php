<?php

require_once('./Initialize.php');

class QueryTest extends StyxUnitTest {
	
	public function testInjection(){
		/* Injections used by the Inject-Me Firefox Extension */
		$injections = array(
			"1 OR 1=1",
			"1' OR '1'='1",
			"1'1",
			"1 EXEC XP_",
			"1 AND 1=1",
			"1' AND 1=(SELECT COUNT(*) FROM tablenames); --",
			"1 AND USER_NAME() = 'dbo'",
			"'; DESC users; --",
			"1'1",
			"1' AND non_existant_table = '1",
			"' OR username IS NOT NULL OR username = '",
			"1 AND ASCII(LOWER(SUBSTRING((SELECT TOP 1 name FROM sysobjects WHERE xtype='U'), 1, 1))) > 116",
			"1 UNION ALL SELECT 1,2,3,4,5,6,name FROM sysObjects WHERE xtype = 'U' --",
			"1 UNI/**/ON SELECT ALL FROM WHERE",
			"%31%27%20%4F%52%20%27%31%27%3D%27%31",
			"&#x31;&#x27;&#x20;&#x4F;&#x52;&#x20;&#x27;&#x31;&#x27;&#x3D;&#x27;&#x31;",
			"&#49&#39&#32&#79&#82&#32&#39&#49&#39&#61&#39&#49",
		);
		
		foreach($injections as $inject)
			Database::select('news', false)->where(array(
				'id' => $inject,
			))->fetch();
		
		// Errno should still be 0
		$this->assertEqual(mysql_errno(), 0);
	}
	
}