<?php namespace Dstokesy\Forms\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Dstokesy\Forms\Models\Entry;
use Event;

class Entries extends ReportWidgetBase
{
    public function render()
	{
		$query = Entry::isUnRead();

		Event::fire('dstokesy.forms.entriesWidget.beforeCount', [$this, &$query]);
		
		$this->vars['count'] = $query->count();

	    return $this->makePartial('widget');
	}

	public function defineProperties()
	{
	    return [];
	}
}