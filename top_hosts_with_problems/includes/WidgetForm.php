<?php declare(strict_types = 0);

namespace Modules\TopHostsWithProblems\Includes;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};

use Zabbix\Widgets\Fields\{
	CWidgetFieldColor,
	CWidgetFieldIntegerBox,
	CWidgetFieldMultiSelectGroup,
	CWidgetFieldMultiSelectHost,
	CWidgetFieldRadioButtonList,
	CWidgetFieldSeverities,
	CWidgetFieldTags,
	CWidgetFieldTextBox,
	CWidgetFieldThresholds,
	CWidgetFieldTimePeriod
};

use CWidgetsData;

/**
 * Top hosts with problems widget form.
 */
class WidgetForm extends CWidgetForm {

	private const DEFAULT_HOST_COUNT = 10;

	public function addFields(): self {
		return $this
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldMultiSelectGroup('groupids', _('Host groups'))
			)
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldMultiSelectHost('hostids', _('Hosts'))
			)
			->addField(
				(new CWidgetFieldColor('base_color', _('Base color')))->setDefault('FF0000')
			)
			->addField(
				new CWidgetFieldTextBox('problem', _('Problem'))
			)
			->addField(
				new CWidgetFieldSeverities('severities', _('Severity'))
			)
			->addField(
				(new CWidgetFieldRadioButtonList('evaltype', _('Problem tags'), [
					TAG_EVAL_TYPE_AND_OR => _('And/Or'),
					TAG_EVAL_TYPE_OR => _('Or')
				]))->setDefault(TAG_EVAL_TYPE_AND_OR)
			)
			->addField(
				new CWidgetFieldTags('tags')
			)
			->addField(
				(new CWidgetFieldTimePeriod('time_period', _('Time period')))
					->setDefault([
						CWidgetField::FOREIGN_REFERENCE_KEY => CWidgetField::createTypedReference(
							CWidgetField::REFERENCE_DASHBOARD, CWidgetsData::DATA_TYPE_TIME_PERIOD
						)
					])
					->setDefaultPeriod(['from' => 'now-1h', 'to' => 'now'])
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldIntegerBox('show_lines', _('Host limit'), ZBX_MIN_WIDGET_LINES,
					ZBX_MAX_WIDGET_LINES
				))
					->setDefault(self::DEFAULT_HOST_COUNT)
					->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				new CWidgetFieldThresholds('thresholds', _('Thresholds'), false)
			);
	}
}
