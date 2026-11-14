<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of ParseFlow for BASE3 Framework.
 *
 * ParseFlow supplies graph-based parser services.
 * It discovers parser capabilities and plans conversion routes.
 * The parser workflow runs through existing module contracts.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 ***********************************************************************/


namespace Base3\ParseFlow\SettingsTab;

use Base3\UiFoundation\Settings\AbstractAdministrationSettingsTab;

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
