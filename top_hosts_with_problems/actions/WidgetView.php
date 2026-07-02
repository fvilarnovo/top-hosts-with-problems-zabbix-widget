<?php declare(strict_types = 0);

namespace Modules\TopHostsWithProblems\Actions;

use API,
	CArrayHelper,
	CControllerDashboardWidgetView,
	CControllerResponseData;

class WidgetView extends CControllerDashboardWidgetView {

	protected function init(): void {
		parent::init();

		$this->addValidationRules([
			'has_custom_time_period' => 'in 1'
		]);
	}

	protected function doAction(): void {
		$data = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'info' => $this->makeWidgetInfo(),
			'base_color' => '#'.$this->fields_values['base_color'],
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		// Editing template dashboard?
		if ($this->isTemplateDashboard() && !$this->fields_values['override_hostid']) {
			$data['error'] = _('No data found');
		}
		else {
			$data['hosts'] = $this->getHosts();
			$data['thresholds'] = $this->fields_values['thresholds'];
			$data['error'] = null;
		}

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getThresholdColor(int $value): string {
		$color = $this->fields_values['base_color'];
		$thresholds = $this->fields_values['thresholds'];

		CArrayHelper::sort($thresholds, [
			['field' => 'threshold', 'order' => ZBX_SORT_DOWN]
		]);

		foreach ($thresholds as $threshold) {
			if ($value >= (int) $threshold['threshold']) {
				return '#'.$threshold['color'];
			}
		}

		return '#'.$color;
	}

	private function getHosts(): array {
		$groupids = !$this->isTemplateDashboard() && $this->fields_values['groupids']
			? getSubGroups($this->fields_values['groupids'])
			: null;

		if ($this->isTemplateDashboard()) {
			$hostids = $this->fields_values['override_hostid'];
		}
		else {
			$hostids = $this->fields_values['hostids'] ?: null;
		}

		// Count problem events per trigger within the selected time period.
		$db_problems = API::Event()->get([
			'countOutput' => true,
			'groupBy' => ['objectid'],
			'groupids' => $groupids,
			'hostids' => $hostids,
			'source' => EVENT_SOURCE_TRIGGERS,
			'object' => EVENT_OBJECT_TRIGGER,
			'value' => TRIGGER_VALUE_TRUE,
			'time_from' => $this->fields_values['time_period']['from_ts'],
			'time_till' => $this->fields_values['time_period']['to_ts'],
			'search' => [
				'name' => $this->fields_values['problem'] !== '' ? $this->fields_values['problem'] : null
			],
			'trigger_severities' => $this->fields_values['severities'] ?: null,
			'evaltype' => $this->fields_values['evaltype'],
			'tags' => $this->fields_values['tags'] ?: null,
			'sortfield' => ['rowscount'],
			'sortorder' => ZBX_SORT_DOWN,
			'limit' => ZBX_MAX_WIDGET_LINES
		]);

		if (!$db_problems) {
			return [];
		}

		$db_problems = array_column($db_problems, null, 'objectid');

		// Get triggers with their hosts so problem counts can be aggregated per host.
		$db_triggers = API::Trigger()->get([
			'output' => [],
			'selectHosts' => ['hostid', 'name', 'status'],
			'triggerids' => array_keys($db_problems),
			'preservekeys' => true
		]);

		$hosts_data = [];

		foreach ($db_triggers as $triggerid => $trigger) {
			$problem_count = $db_problems[$triggerid]['rowscount'];

			foreach ($trigger['hosts'] as $host) {
				if (!array_key_exists($host['hostid'], $hosts_data)) {
					$hosts_data[$host['hostid']] = [
						'hostid' => $host['hostid'],
						'name' => $host['name'],
						'status' => $host['status'],
						'problem_count' => 0
					];
				}

				$hosts_data[$host['hostid']]['problem_count'] += $problem_count;
			}
		}

		CArrayHelper::sort($hosts_data, [
			['field' => 'problem_count', 'order' => ZBX_SORT_DOWN],
			'name'
		]);

		foreach ($hosts_data as $hostid => $host) {
			$hosts_data[$hostid]['threshold_color'] = $this->getThresholdColor($host['problem_count']);
		}

		$hosts_data = array_slice($hosts_data, 0, $this->fields_values['show_lines'], true);

		return $hosts_data;
	}

	/**
	 * Make widget specific info to show in widget's header.
	 */
	private function makeWidgetInfo(): array {
		$info = [];

		if ($this->hasInput('has_custom_time_period')) {
			$info[] = [
				'icon' => ZBX_ICON_TIME_PERIOD,
				'hint' => relativeDateToText($this->fields_values['time_period']['from'],
					$this->fields_values['time_period']['to']
				)
			];
		}

		return $info;
	}
}
