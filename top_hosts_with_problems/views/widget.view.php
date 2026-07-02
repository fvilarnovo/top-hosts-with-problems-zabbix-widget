<?php declare(strict_types = 0);

/**
 * Top hosts with problems widget view.
 *
 * @var CView $this
 * @var array $data
 */

$view = new CWidgetView($data);

$table = (new CTableInfo())
	->setHeader([_('Host'), _('Problems')])
	->addClass(ZBX_STYLE_LIST_TABLE_STICKY_HEADER);

if ($data['error'] !== null) {
	$table->setNoDataMessage($data['error'], null, ZBX_ICON_SEARCH_LARGE);
}
else {
	foreach ($data['hosts'] as $hostid => $host) {
		$url_host = (new CUrl('zabbix.php'))
			->setArgument('action', 'problem.view')
			->setArgument('filter_set', '1')
			->setArgument('show', TRIGGERS_OPTION_ALL)
			->setArgument('hostids', [$host['hostid']]);

		$table->addRow([
			(new CLink($host['name'], $url_host->getUrl()))
				->addClass(ZBX_STYLE_WORDBREAK)
				->addClass($host['status'] == HOST_STATUS_NOT_MONITORED ? ZBX_STYLE_COLOR_NEGATIVE : null),
			(new CSpan($host['problem_count']))
				->addStyle('color: '.$host['threshold_color'].'; font-weight: bold;')
		]);
	}
}

if ($data['info']) {
	$view->setVar('info', $data['info']);
}

$view
	->addItem($table)
	->show();
