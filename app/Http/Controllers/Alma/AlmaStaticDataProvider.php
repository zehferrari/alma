<?php

namespace App\Http\Controllers\Alma;


class AlmaStaticDataProvider {
  /**
   * Determine patron type / stat class / home library based on membership submission info.
   *
   * @param string $type The type of account to get fields for
   * @param string $service (for hospital members)
   * @param string $classification (for hospital members)
   * @param string $institution (for reciprocal members)
   * @return array
   */
  static function _loadAlmaFields($type, $service = '', $classification = '', $institution = '')
  {
    $data = [
      'atype' => '',
      'ptype' => '',
      'statClass' => '',
      'homeLib' => 'sshia',
    ];

    // Stat class can be either:
    //  - A positive integer value which means it's known based on the submission type
    //  - FALSE if the sta class is not applicable
    //  - -1 if the stat class cannot be determined based on the data submitted
    //       and is selected by staff at the desk
    switch ($type) {
      case 'alumninew':
        $data['atype'] = "ALUMNI";
        $data['ptype'] = 4;
        $data['statClass'] = 146;
        break;
      case 'alumni':
        $data['atype'] = "ALUMNI";
        $data['ptype'] = 4;
        $data['statClass'] = 78;
        break;
      case 'hospital':
        $h = self::_getAlmaDetailsForHospital($service, $classification);
        $data['atype'] = $h['atype'];
        $data['ptype'] = $h['ptype'];
        $data['statClass'] = $h['statClass'];
        $data['homeLib'] = $h['homeLib'];
        break;
      case 'alumnifriends':
        $data['atype'] = "COMMU";
        $data['ptype'] = 8;
        $data['statClass'] = 76;
        break;
      case 'community':
        $data['atype'] = "COMMU";
        $data['ptype'] = 8;
        $data['statClass'] = 76;
        break;
      case 'friend':
        $data['atype'] = "COMMU";
        $data['ptype'] = 8;
        $data['statClass'] = 129;
        break;
      case 'cyberschool':
        $data['atype'] = "SCHOOL";
        $data['ptype'] = 7;
        $data['statClass'] = 5;
        break;
      case 'retired':
        $data['atype'] = "COMMU";
        $data['ptype'] = 8;
        $data['statClass'] = 117;
        break;
      case 'reciprocal':
        $data['atype'] = "COMMU";
        $data['ptype'] = 8;
        $r = self::_getAlmaDetailsForReciprocal($institution);
        $data['statClass'] = $r['statClass'];
        break;
      case 'icte':
        $data['atype'] = "ICTE";
        $data['ptype'] = 5;
        $data['statClass'] = false;
        break;
      case 'proxy':
        $data['atype'] = "PROXY";
        $data['ptype'] = 32;
        $data['statClass'] = false;
        break;
      case 'associate':
        $data['atype'] = "ASSOCIATE";
        $data['ptype'] = 25;
        $data['statClass'] = -1;
        break;
      case 'visitors':
        $data['atype'] = "COMMU";
        $data['ptype'] = 8;
        break;
      case 'awaitingaurion':
        $data['atype'] = "AURION";
        $data['ptype'] = 15;
        break;
    }

    return $data;
  }

  /**
   * Maps institution to Alma home library stat class
   *
   * @param string $institution of reciprocal member
   * @return bool
   */
  static function _getAlmaDetailsForReciprocal($institution)
  {
    $data = json_decode(file_get_contents(__DIR__ . '/data/reciprocalMapping.json'), true);

    if (!empty($institution) && array_key_exists($institution, $data)) {
      return $data[$institution];
    } else {
      return $data['Other'];
    }
  }

  /**
   * Maps service/classification to Alma home library / atype / stat class
   *
   * @param string $service The hospital service
   * @param string $classification The hospital classification
   * @return bool
   */
  static function _getAlmaDetailsForHospital($service, $classification = '')
  {
    $arHospital = json_decode(file_get_contents(__DIR__ . '/data/hospitalServiceToTypeMapping.json'), true);

    if (array_key_exists($service, $arHospital)) {
      if (
        !empty($classification)
        && array_key_exists($classification, $arHospital[$service])
      ) {
        return $arHospital[$service][$classification];
      }
      return $arHospital[$service]['default'];
    } else {
      return false;
    }
  }

  /**
   * Returns a list of account types this form handles
   *
   * @param bool $expanded Set to TRUE to include descriptions/titles
   * @return array
   */
  static function _getAccountTypes($expanded = false)
  {
    $types = json_decode(file_get_contents(__DIR__ . '/data/almaAccountTypes.json'), true);

    if (!$expanded) {
      $collapsed = [];
      foreach ($types as $type) {
        $collapsed[] = $type['value'];
      }
      return $collapsed;
    }
    return $types;
  }

  /**
   * Returns a list of classifications (displayed on Teaching Hospital Staff form)
   *
   * @return array
   */
  static function _getClassifications()
  {
    return json_decode(file_get_contents(__DIR__ . '/data/hospitalServiceClassifications.json'), true);
  }

  /**
   * Returns a list of hospital services (displayed on Teaching Hospital Staff form)
   *
   * @return array
   */
  static function _getHospitalServices()
  {
    return json_decode(file_get_contents(__DIR__ . '/data/hospitalServices.json'), true);
  }

  /**
   * Returns a list of hospital staff email domains (displayed on Teaching Hospital Staff form)
   *
   * @return array
   */
  static function _getHospitalMailDomains()
  {
    return [
      'health.qld.gov.au', 'mater.org.au', 'mmri.mater.org.au', 'mater.uq.edu.au', 'library.uq.edu.au'
    ];
  }

  /**
   * Returns a list of hospital staff employee types (displayed on Teaching Hospital Staff form)
   *
   * @return array
   */
  static function _getHospitalEmatypes()
  {
    return ['Continuing', 'Casual', 'Contract'];
  }

  /**
   * Returns a list of reciprocal institutions (displayed on reciprocal borrowers form)
   *
   * @return array
   */
  static function _getReciprocalInstitutions()
  {
    return json_decode(file_get_contents(__DIR__ . '/data/reciprocalInstitutions.json'), true);
  }

  /**
   * Returns a list of titles
   *
   * @return array
   */
  static function _getTitles()
  {
    return [
      'Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Sr', 'Prof', 'AProf'
    ];
  }

  /**
   * Returns a list of Cyberschools
   *
   * @return array
   */
  static function _getCyberschools()
  {
    return json_decode(file_get_contents(__DIR__ . '/data/cyberschools.json'), true);
  }

  /**
   * @return \Illuminate\Http\JsonResponse
   */
  static function _getMembershipData()
  {
    return [
      'hospital' => [
        'classifications' => AlmaStaticDataProvider::_getClassifications(),
        'services' => AlmaStaticDataProvider::_getHospitalServices(),
        'types' => AlmaStaticDataProvider::_getHospitalEmatypes()
      ],
      'accountTypes' => AlmaStaticDataProvider::_getAccountTypes(true),
      'accountTypesCollapsed' => AlmaStaticDataProvider::_getAccountTypes(),
      'titles' => AlmaStaticDataProvider::_getTitles(),
      'reciprocal' => [
        'institutions' => AlmaStaticDataProvider::_getReciprocalInstitutions()
      ],
    ];
  }

}