<?php declare(strict_types=1);

namespace ParseFlow\SettingsTab;

use UiFoundation\Settings\AbstractAdministrationSettingsTab;

final class ParseflowparseradminSettingsTab extends AbstractAdministrationSettingsTab {

	public function getParentTabName(): ?string {
		return 'parseflowsettingstab';
	}

	public function getDisplayName(): ?string {
		return 'parseflowparseradmindisplay';
	}

	public function getOrder(): int {
		return 20;
	}
}
