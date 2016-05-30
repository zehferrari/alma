<?php

namespace App\Http\Controllers\Alma;

class DataMigrationServiceProvider extends DataMigration {

  /*
   * Translate fields from the membership dynamoDB table to Alma
   */
  static function toAlma($obj) {
    $class = AlmaStaticDataProvider::_loadAlmaFields(
      $obj['type'], 
      (($obj['type'] === 'hospital')?$obj['hospitalService']:''),
      (($obj['type'] === 'hospital')?$obj['hospitalClass']:''),
      (($obj['type'] === 'reciprocal' && array_key_exists('reciprocalInstitution', $obj)) ? $obj['reciprocalInstitution'] : '')
    );
    $alma = [
      
      // Alma User fields API documentation:
      // https://developers.exlibrisgroup.com/alma/apis/xsd/rest_user.xsd

      "gender"          => [
        "value"         => "",
        "desc"          => ""
      ],
//    "password"        => self::getField($obj, "userPassword"),
      "status"          => [
        "value"         => self::getMembershipStatus($obj),
        "desc"          => ucfirst(strtolower(self::getMembershipStatus($obj)))
      ],
      "record_type"     => [
        "value"         => "PUBLIC",
        "desc"          => "Public"
      ],
      "primary_id"      => self::getField($obj, "uid"),
      "first_name"      => self::getField($obj, "firstName"),
      "middle_name"     => "",
      "last_name"       => self::getField($obj, "sn"),
      "full_name"       => self::getField($obj, "name"),
      "user_title"      => [
        "value"         => self::getField($obj, "title"),
        "desc"          => ucfirst(strtolower(self::getField($obj, "title")))
      ],
      "job_description" => "",
      "user_group"      => [
        "value"         => $class['atype'],
        "desc"          => ucfirst(strtolower($class['atype']))
      ],
      "preferred_language" => [
        "value"         => "en",
        "desc"          => "English"
      ],
      "birth_date"      => self::fixAlmaDate(self::getField($obj, "dateOfBirth")),
      "expiry_date"     => self::fixAlmaDate(self::getField($obj, "expiresOn")),
      "purge_date"      => self::fixAlmaDate(self::getField($obj, "expiresOn")),

      // https://developers.exlibrisgroup.com/blog/Users-API-working-with-external-internal-users
      "account_type"    => [
        "value"         => "EXTERNAL",
        "desc"          => "External"
      ],
      "external_id"     => "SIS",

      "force_password_change" => "",

      "contact_info" => [
        'address'       => self::popMembershipAddresses($obj),
        'email'         => self::popMembershipEmails($obj),
        'phone'         => self::popMembershipPhones($obj)
      ],

      // https://developers.exlibrisgroup.com/alma/apis/xsd/rest_user.xsd?tags=GET#user_identifiers
      "user_identifier" => [
        [
          "value"       => self::getField($obj, "barcode"),
          "note"        => null,
          "status"      => self::getMembershipStatus($obj),
          "id_type"     => [
            "value"     => "BARCODE",
            "desc"      => "Barcode"
          ],
          "segment_type"=> "External"
        ]
      ],

      // https://developers.exlibrisgroup.com/alma/apis/xsd/rest_user.xsd?tags=GET#user_roles
      "user_role"       => [
        [
          "status"        => [
            "value"       => strtoupper(self::getMembershipStatus($obj)),
            "desc"        => ucfirst(strtolower(self::getMembershipStatus($obj)))
          ],
          "scope"         => [
            "value"       => "61UQ_INST",
            "desc"        => "University of Queensland"
          ],
          "role_type"     => [
            "value"       => "200",
            "desc"        => "Patron"
          ],
          "parameter"     => []
        ]
      ],

      // https://developers.exlibrisgroup.com/alma/apis/xsd/rest_user.xsd?tags=GET#user_statistic
      "user_statistic"  => [
        [
          "statistic_category" => [
            "value"       => $class['statClass'],
            "desc"        => 'NON UQ USER'
          ],
          "segment_type"  => "External"
        ]
      ],

      "proxy_for_user"  => []
    ];

    $alma['user_note'] = [];

    // Save LDAP(/millennium) Ptype
    if ($class and isset($class['ptype']) and $class['ptype']) {
      $alma['user_note'][] = [
        "note_type" => [
          "value"       => "OTHER",
          "desc"        => 'PTYPE'
        ],
        "note_text"  => "PTYPE: ".$class['ptype']
      ];
    }

    // Save Membership type
    if ($obj and isset($obj['type']) and $obj['type']) {
      $alma['user_note'][] = [
        "note_type" => [
          "value"       => "OTHER",
          "desc"        => 'MTYPE'
        ],
        "note_text"  => "MTYPE: ".$obj['type']
      ];
    }

    $payment = self::popMembershipPayment($obj);
    if ($payment) {
      // https://developers.exlibrisgroup.com/alma/apis/xsd/rest_user.xsd?tags=POST#user_note
      $alma['user_note'][] = [
        "note_type" => [
          "value"       => "OTHER",
          "desc"        => 'Payment Description'
        ],
        "note_text"  => "[Payment Info] - ".$payment
      ];
    }

    return $alma;
  }

  /*
   * Translate fields from Alma to Membership (dynamoDB)
   */
  static function toMembership($obj) {
    $obj = json_decode($obj, true);
    $membership = [];
    if ($obj) {

      // The field ptype can get a different value that the one set on the Membership module,
      // ie: in Alma alumininew is commu.
      // the same goes with ID (external_id)
      // so to avoid any overwriting in the Membership app
      // (which could potentially break the integration),
      // Im not bring these fields back

      $fields = [
        "uid"             => "primary_id",
//      "id"              => "external_id",
        "atype"           => "user_group",
        "userPassword"    => "password",
        "title"           => "user_title",
        "name"            => "full_name",
        "firstName"       => "first_name",
        "sn"              => "last_name",
        "dateOfBirth"     => "birth_date",
        "expiresOn"       => "expiry_date",
        "url"             => "web_site_url"
      ];
      foreach($fields as $dynamo => $alma) {
        $value = self::getField($obj, $alma);
        if ($value) {
          $membership[$dynamo] = $value;
        }
      }

      // Dates
      $dates = [
        "dateOfBirth"     => "birth_date",
        "expiresOn"       => "expiry_date"
      ];
      foreach($dates as $dynamo => $alma) {
        $value = self::fixMembershipDate(self::getField($obj, $alma));
        if ($value) {
          $membership[$dynamo] = $value;
        }
      }

      $barcode = self::getAlmaBarCode($obj);
      if ($barcode) {
        $membership['barcode'] = $barcode;
      }

      $status = self::getAlmaStatus($obj);
      if ($status) {
        $membership['status'] = $status;
      }

      if (isset($obj['user_note']) and $obj['user_note']) {
        $ptype = self::getAlmaPtype($obj['user_note']);
        if ($ptype) {
          $membership['ptype'] = $ptype;
        }
        $mtype = self::getAlmaMembershipPtype($obj['user_note']);
        if ($mtype) {
          $membership['type'] = $mtype;
        }
        $payment = self::popAlmaMembershipPayment($obj['user_note']);
        if ($payment) {
          $membership = array_merge($membership, $payment);
        }
      }

      if (isset($obj["user_statistic"]) and $obj["user_statistic"] and isset($obj["user_statistic"][0])) {
        $value = self::getField($obj["user_statistic"][0], "statistic_category");
        if ($value) {
          $membership["statClass"] = $value;
        }
      }

      if (isset($obj['contact_info']) and $obj['contact_info']) {
        if (isset($membership['atype']) and isset($obj['contact_info']['address']) and $obj['contact_info']['address']) {
          $addresses = self::popAlmaAddresses($membership['atype'], $obj['contact_info']['address']);
          if ($addresses) {
            $membership = array_merge($membership, $addresses);
          }
        }
        if (isset($obj['contact_info']['email']) and $obj['contact_info']['email']) {
          $email = self::popAlmaEmails($obj['contact_info']['email']);
          if ($email) {
            $membership = array_merge($membership, $email);
          }
        }
        if (isset($obj['contact_info']['phone']) and $obj['contact_info']['phone']) {
          $phone = self::popAlmaPhones($obj['contact_info']['phone']);
          if ($phone) {
            $membership = array_merge($membership, $phone);
          }
        }
      }
    }

    return $membership;
  }

  /*
   *  Get users summary
   */
  static function getUserSummary($obj) {
    // Although the bookings field is not in use,
    // I've kept it to maintain compatibility with current Millenium responses

    return [
      'recordNumber' => self::getAlmaBarCode($obj),
      'bookings' => "",
      'fines' => self::getField($obj, 'fees'),
      'holds' => self::getField($obj, 'requests'),
      'checkedOutItems' => self::getField($obj, 'loans')
    ];
  }
}
