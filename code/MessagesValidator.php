<?php

class MessagesValidator extends RequiredFields
{
	protected static $jquery_included = false;
	
	protected static $field2class = array (
		'NumericField' => 'digits',
		'PhoneNumberField' => 'phoneUS',
		'DateField' => 'date',
		'CalendarDateField' => 'date',
		'DatePickerField' => 'date',
		'CompositeDateField' => 'date',
		'DMYDateField' => 'date',
		'EmailField' => 'email',
		'CreditCardField' => 'creditcard'
	);
	
	protected static function get_class_for($fieldClass)
	{
		if(isset(self::$field2class[$fieldClass]))
			return self::$field2class[$fieldClass];
		return false;
	}
	
	public static function jquery_included()
	{
		self::$jquery_included = true;
	}
	
	public function includeJavascriptValidation()
	{

		if($this->required) {
			$fields = $this->form->Fields();	
			foreach($this->required as $name) {
				$field = $fields->dataFieldByName($name);
				$field->addExtraClass("required");
				if($class = self::get_class_for($field->class))
					$field->addExtraClass($class);
			}
		}
		$form = $this->form->FormName();
	}
	
	public function javascript() {return;	}
	
}

