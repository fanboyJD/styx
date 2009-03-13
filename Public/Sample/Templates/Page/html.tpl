<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>${app.name}</title>
	<base href="${app.link}"></base>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	${package.style.css}${package.ie.js}${package.package1.js}
	${scripts}
	<link rel="shortcut icon" href="favicon.png" />
	<link rel="alternate" type="application/rss+xml" title="${rss.title}" href="${rss.link}" />
</head>
<body>
<div class="wrapper">
	<div id="languages">${languages}</div>
	<a id="logo" href="${app.link}"></a>
	${layer.pagemenu}
	${framework.description}
	<div id="text">
		${layer}
		<div class="clear"></div>
	</div>
</div>
<div id="footer">
	<div>${user}</div>
	<div style="float: left; margin-left: 10px;">
		${styx}
	</div>
</div>
</body>
</html>
