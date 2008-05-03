<label>${:caption}</label>
<div id="${id}">
<?php
foreach($this->options[':elements'] as $val)
	echo '<label><input value="'.$val['value'].'"'.$this->implode(array('skipValue', 'skipId')).' '.($val['value']==$this->options['value'] ? 'checked="checked" ' : '').'/> '.$val[':caption'].'</label>';
?>
</div>