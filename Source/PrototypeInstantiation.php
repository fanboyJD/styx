<?php
if(empty(self::$Storage['Classes']['layer'])){ class Layer extends LayerPrototype {} }
if(empty(self::$Storage['Classes']['model'])){ class Model extends ModelPrototype {} }
if(empty(self::$Storage['Classes']['object'])){ class Object extends ObjectPrototype {} }
if(empty(self::$Storage['Classes']['validator'])){ class Validator extends ValidatorPrototype {} }
if(empty(self::$Storage['Classes']['data'])){ class Data extends DataPrototype {} }
if(empty(self::$Storage['Classes']['user'])){ class User extends UserPrototype {} }