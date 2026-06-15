<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Compatibility helpers for Diffusion module.
 */
class DiffusionCompatibility
{
	public const MIN_DOLIBARR_VERSION = '20.0.0';
	public const MIN_PHP_VERSION = '8.0.0';

	/**
	 * Check current Dolibarr version.
	 *
	 * @param string $version Version to compare with
	 * @return bool
	 */
	public static function isDolibarrVersionAtLeast($version)
	{
		return defined('DOL_VERSION') && version_compare(DOL_VERSION, $version, '>=');
	}

	/**
	 * Check current PHP version.
	 *
	 * @param string $version Version to compare with
	 * @return bool
	 */
	public static function isPhpVersionAtLeast($version)
	{
		return version_compare(PHP_VERSION, $version, '>=');
	}

	/**
	 * Return feature compatibility matrix.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function getFeatures()
	{
		return array(
			'core_module' => array(
				'label' => 'DiffusionCompatibilityFeatureCore',
				'description' => 'DiffusionCompatibilityFeatureCoreDesc',
				'min_dolibarr' => self::MIN_DOLIBARR_VERSION,
				'min_php' => self::MIN_PHP_VERSION,
				'checks' => array(),
			),
			'multicompany_documents' => array(
				'label' => 'DiffusionCompatibilityFeatureMulticompanyDocuments',
				'description' => 'DiffusionCompatibilityFeatureMulticompanyDocumentsDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => self::MIN_PHP_VERSION,
				'checks' => array('getMultidirOutput'),
			),
			'native_notifications' => array(
				'label' => 'DiffusionCompatibilityFeatureNotifications',
				'description' => 'DiffusionCompatibilityFeatureNotificationsDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => self::MIN_PHP_VERSION,
				'checks' => array(),
			),
			'native_agenda' => array(
				'label' => 'DiffusionCompatibilityFeatureAgenda',
				'description' => 'DiffusionCompatibilityFeatureAgendaDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => self::MIN_PHP_VERSION,
				'checks' => array('ActionComm'),
			),
		);
	}

	/**
	 * Check if a feature is available.
	 *
	 * @param string $feature Feature code
	 * @return bool
	 */
	public static function isFeatureAvailable($feature)
	{
		$features = self::getFeatures();
		if (empty($features[$feature])) {
			return false;
		}

		return self::getFeatureStatus($feature, $features[$feature])['available'];
	}

	/**
	 * Return unavailable feature statuses.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function getUnavailableFeatures()
	{
		$unavailable = array();
		foreach (self::getFeatures() as $code => $feature) {
			$status = self::getFeatureStatus($code, $feature);
			if (empty($status['available'])) {
				$unavailable[$code] = $status;
			}
		}

		return $unavailable;
	}

	/**
	 * Return computed feature status.
	 *
	 * @param string $code Feature code
	 * @param array<string,mixed> $feature Feature definition
	 * @return array<string,mixed>
	 */
	public static function getFeatureStatus($code, $feature)
	{
		$available = true;
		$reason = '';

		if (!self::isDolibarrVersionAtLeast($feature['min_dolibarr'])) {
			$available = false;
			$reason = 'DiffusionCompatibilityReasonDolibarr';
		}
		if ($available && !self::isPhpVersionAtLeast($feature['min_php'])) {
			$available = false;
			$reason = 'DiffusionCompatibilityReasonPhp';
		}
		if ($available && !empty($feature['checks']) && is_array($feature['checks'])) {
			foreach ($feature['checks'] as $check) {
				if ($check === 'getMultidirOutput' && !function_exists('getMultidirOutput')) {
					$available = false;
					$reason = 'DiffusionCompatibilityReasonFunctionMissing';
					break;
				}
				if ($check === 'ActionComm' && !class_exists('ActionComm')) {
					$available = false;
					$reason = 'DiffusionCompatibilityReasonClassMissing';
					break;
				}
			}
		}

		$feature['code'] = $code;
		$feature['available'] = $available;
		$feature['reason'] = $available ? 'DiffusionCompatibilityReasonAvailable' : $reason;

		return $feature;
	}
}
