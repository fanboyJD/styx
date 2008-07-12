<label>${:caption}${:add}</label>
<select${attributes}>
<?php
foreach($this->options[':elements'] as $val)
	echo '<option value="'.$val['value'].'"'.$this->implode(array('skipValue', 'skipId', 'skipName')).($val['value']==$this->options['value'] ? ' selected="selected"' : '').'>'.$val[':caption'].'</option>';
?>
</select>