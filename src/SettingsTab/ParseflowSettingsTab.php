<?php declare(strict_types=1);

namespace ParseFlow\SettingsTab;

use UiFoundation\Settings\AbstractSettingsTab;

final class ParseflowSettingsTab extends AbstractSettingsTab {

	public function getOrder(): int {
		return 75;
	}
}
