<?php declare(strict_types=1);

namespace ParseFlow\SettingsTab;

use UiFoundation\Settings\AbstractAdministrationSettingsTab;

final class ParserexploreradminSettingsTab extends AbstractAdministrationSettingsTab {

	public function getParentTabName(): ?string {
		return 'parseflowsettingstab';
	}

	public function getDisplayName(): ?string {
		return 'parserexploreradmindisplay';
	}

	public function getOrder(): int {
		return 10;
	}
}
