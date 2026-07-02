<?php declare(strict_types = 0);

namespace Modules\TopHostsWithProblems;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public function getDefaultName(): string {
		return _('Top hosts with problems');
	}
}
