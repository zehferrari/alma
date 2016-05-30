<?php

namespace App\Http\Controllers\Alma;

class DataMigration {

  /**
   * Addresses field map
   **/
  const home = [
    "homeAddress_0"         => "line1",
    "homeAddress_1"         => "line2",
    "homeAddress_city"      => "city",
    "homeAddress_state"     => "state_province",
    "homeAddress_postcode"  => "postal_code"
  ];
  const school = [
    "cyberschoolAddress_0"  => "line1",
    "cyberschoolAddress_1"  => "line2"
  ];
  const associate = [
    "associateAddress_0"    => "line1",
    "associateAddress_1"    => "line2",
    "associateAddress_2"    => "line3"
  ];
  const hospital = [
    "hospitalAddress_0"     => "line1",
    "hospitalAddress_1"     => "line2",
    "hospitalAddress_2"     => "line3",
    "hospitalService"       => "line4"
  ];
  const homeAddress = [
    "address_type"    => [
      [
        "value"         => "home",
        "desc"          => "Home"
      ]
    ]
  ];
  const workAddress = [
    "address_type"    => [
      [
        "value"         => "work",
        "desc"          => "Work"
      ]
    ]
  ];

  /**
   * Return the value for an specific field in an array
   **/
  static function getField($obj, $label) {
    if ($obj and isset($obj[$label])) {
      $reply = $obj[$label];
      return (isset($reply['value']) ? $reply['value'] : (isset($reply[0]['value']) ? $reply[0]['value'] : $reply));
    }
    return null;
  }

  /**
   * Format date from Membership to Alma
   **/
  static function fixAlmaDate($date) {
    return strftime("%Y-%m-%d", strtotime($date))."Z";
  }

  /**
   * Format date from Alma to Membership
   **/
  static function fixMembershipDate($date) {
    return strftime("%d-%m-%Y", strtotime($date));
  }

  /**
   * Detect membership status from dynamoDB to Alma
   **/
  static function getMembershipStatus($obj) {
    if ($obj and isset($obj['status']) and 
        in_array(strtolower($obj['status']), ['confirmed', 'renewing'])) {
        return 'ACTIVE';
    }
    return 'INACTIVE';
  }

  /**
   * Translate status field from Alma to Membership (dynamoDB)
   **/
  static function getAlmaStatus($obj) {
    $status = self::getField($obj, 'status');
    if ($status and strtolower($status) == 'active') {
      return 'Confirmed';
    }
    return 'Unconfirmed';
  }

  /**
   * Get ptype from Alma to Membership (dynamoDB)
   **/
  static function getAlmaPtype($notes) {
    foreach($notes as $key=>$note) {
      if ($note and isset($note['note_text']) and substr($note['note_text'],0,7) == 'PTYPE: ') {
        return str_replace('PTYPE: ', '', $note['note_text']);
      } 
    }
    return null;
  }

  /**
   * Get ptype from Alma to Membership (dynamoDB)
   **/
  static function getAlmaMembershipPtype($notes) {
    foreach($notes as $key=>$note) {
      if ($note and isset($note['note_text']) and substr($note['note_text'],0,7) == 'MTYPE: ') {
        return str_replace('MTYPE: ', '', $note['note_text']);
      } 
    }
    return null;
  }

  /**
   * Extracts the users barcode from Alma
   **/
  static function getAlmaBarCode($obj) {
    if ($obj and isset($obj['user_identifier'])) {
      foreach($obj['user_identifier'] as $item) {

        if ($item and isset($item['id_type']) and $item['id_type'] and 
            isset($item['id_type']) and $item['id_type'] and 
            isset($item['id_type']['value']) and isset($item["value"]) and
            in_array(strtolower($item['id_type']['value']), ['01', 'barcode'])) {

          return $item["value"];

        }

      }
    }
    return null;
  }

  /**
   * Populate all addresses from the membership form to Alma
   **/
  static function popMembershipAddresses($obj)
  {
    $address = [];
    if (isset($obj['homeAddress_0']) && $obj['homeAddress_0']) {
      $home = [];
      foreach(self::home as $dynamo => $alma) {
        $value = self::getField($obj, $dynamo);
        if ($value) {
          $home[$alma] = $value;
        }
      }
      if (count($home)) {
        $address[] = array_merge($home, [
          "country"         => [
            "value"         => "AUS",
            "desc"          => "Australia"
          ],
          "preferred"       => true,
          "address_note"    => "",
          "segment_type"    => "External",
          "address_type"    => self::homeAddress['address_type']
        ]);
      }
    }
    if (isset($obj['hospitalAddress_0']) && $obj['hospitalAddress_0']) {
      $hospital = [];
      foreach(self::hospital as $dynamo => $alma) {
        $value = self::getField($obj, $dynamo);
        if ($value) {
          $hospital[$alma] = $value;
        }
      }
      $note = [];
      $class = self::getField($obj, "hospitalClass");
      if ($class) {
        $note[] = "Class: ".$class;
      }
      $empType = self::getField($obj, "hospitalEmpType");
      if ($class) {
        $note[] = "EmpType: ".$empType;
      }
      if (count($note)) {
        $hospital["address_note"] = implode(' | ', $note);
      }
      if (count($hospital)) {
        $address[] = array_merge($hospital, self::workAddress);
      }
    }
    if (isset($obj['cyberschoolAddress_0']) && $obj['cyberschoolAddress_0']) {
      $school = [];
      foreach(self::school as $dynamo => $alma) {
        $value = self::getField($obj, $dynamo);
        if ($value) {
          $school[$alma] = $value;
        }
      }
      $enrol = self::getField($obj, "cyberschoolEnrolYear");
      if ($enrol) {
        $school["address_note"] = "Enrol Year: ".$enrol;
      }
      if (count($school)) {
        $address[] = array_merge($school, self::workAddress);
      }
    }
    if (isset($obj['associateAddress_0']) && $obj['associateAddress_0']) {
      $associate = [];
      foreach(self::associate as $dynamo => $alma) {
        $value = self::getField($obj, $dynamo);
        if ($value) {
          $associate[$alma] = $value;
        }
      }
      if (count($associate)) {
        $address[] = array_merge($associate, self::workAddress);
      }
    }
    return $address;
  }

  /**
   * Populate all addresses from Alma to the Membership app
   **/
  static function popAlmaAddresses($atype, $obj) {
    $address = [];
    if ($obj) {
      foreach($obj as $key=>$street) {
        $type = self::getField($street, 'address_type');
        if ($street and isset($street['address_type']) and $type and strtolower($type)=='home') {
          foreach(self::home as $dynamo => $alma) {
            $value = self::getField($street, $alma);
            if ($value) {
              $address[$dynamo] = $value;
            }
          }
        } elseif ($atype) {
          switch($atype) {
            case 'SCHOOL':
              foreach(self::school as $dynamo => $alma) {
                $value = self::getField($street, $alma);
                if ($value) {
                  $address[$dynamo] = $value;
                }
              }

              if (isset($street["address_note"]) and $street["address_note"]) {
                $split = explode(': ', $street["address_note"]);
                if (isset($split[1])) {
                  $address["cyberschoolEnrolYear"] = $split[1];
                }
              }
              break;
            case 'ASSOCIATE':
              foreach(self::associate as $dynamo => $alma) {
                $value = self::getField($street, $alma);
                if ($value) {
                  $address[$dynamo] = $value;
                }
              }
              break;
            default:
              foreach(self::hospital as $dynamo => $alma) {
                $value = self::getField($street, $alma);
                if ($value) {
                  $address[$dynamo] = $value;
                }
              }
              if (isset($street["address_note"]) and $street["address_note"]) {
                $split = explode(' | ', $street["address_note"]);
                foreach($split as $value) {
                  $splitagain = explode(': ', $value);
                  if ($splitagain and isset($splitagain[0]) and isset($splitagain[1])) {
                    $address["hospital".$splitagain[0]] = $splitagain[1];
                  }
                }
              }
          }
        }
      }
    }
    return $address;
  }

  /**
   * Populate all emails from the membership form to Alma
   **/
  static function popMembershipEmails($obj) {
    $email = [];
    if (isset($obj['mail']) && $obj['mail']) {
      $email[] = [
        "preferred"       => true,
        "email_address"   => $obj['mail'],
        "segment_type"    => "External",
        "email_type"      => [
          [
            "value"         => "personal",
            "desc"          => "Personal"
          ]
        ]
      ];
    }
    if (isset($obj['cyberschoolParentMail']) && $obj['cyberschoolParentMail'] &&
      (!isset($obj['mail']) || !$obj['mail'] || $obj['cyberschoolParentMail'] !== $obj['mail'])) {
      $email[] = [
        "preferred"       => !(isset($obj['mail']) && $obj['mail']),
        "email_address"   => $obj['cyberschoolParentMail'],
        "email_type"      => self::workAddress['address_type']
      ];
    }
    return $email;
  }

  /**
   * Populate all emails from Alma to Membership
   **/
  static function popAlmaEmails($obj) {
    $emails = [];
    if ($obj) {
      foreach ($obj as $key => $email) {
        $label = ($email and self::getField($email, "email_type") == "personal") ? "mail" : "cyberschoolParentMail";
        $emails[$label] = self::getField($email, "email_address");
      }
    }
    return $emails;
  }

  /**
   * Populate all phone numbers from the membership form to Alma
   **/
  static function popMembershipPhones($obj) {
    $phone = [];
    if (isset($obj['phone']) && $obj['phone']) {
      $phone[] = [
        "preferred"       => true,
        "phone_number"    => $obj['phone'],
        "phone_type"      => self::homeAddress['address_type']
      ];
    }
    if (isset($obj['cyberschoolPhoneNum']) && $obj['cyberschoolPhoneNum'] &&
      (!isset($obj['phone']) || !$obj['phone'] || $obj['cyberschoolPhoneNum'] !== $obj['phone'])) {
      $phone[] = [
        "preferred"       => true,
        "phone_number"    => $obj['cyberschoolPhoneNum'],
        "phone_type"      => self::workAddress['address_type']
      ];
    }
    return $phone;
  }

  /**
   * Populate all phone numbers from Alma to Membership
   **/
  static function popAlmaPhones($obj) {
    $phones = [];
    if ($obj) {
      foreach ($obj as $key => $phone) {
        $label = ($phone and self::getField($phone, "phone_type") == "home") ? "phone" : "cyberschoolPhoneNum";
        $phones[$label] = self::getField($phone, "phone_number");
      }
    }
    return $phones;
  }

  /**
   * Populate the payment info from Membership
   **/
  static function popMembershipPayment($obj) {
    $fields = [
      'Code',
      'Receipt',
      'Date',
      'Amount',
      'Response'
    ];
    $note = [];
    foreach($fields as $label) {
      $value = self::getField($obj, "payment{$label}");
      if ($value) {
        $note[] = "{$label}: {$value}";
      }
    }
    if (count($note)) {
      return implode(' | ', $note);
    }
    return null;
  }

  /**
   * Populate the payment info from Membership
   **/
  static function popAlmaMembershipPayment($notes) {
    $reply = [];
    foreach($notes as $key=>$note) {
      if ($note and isset($note['note_text']) and substr($note['note_text'],0,17) == '[Payment Info] - ') {
        $payment = str_replace('[Payment Info] - ', '', $note['note_text']);
        $fields = explode(' | ', $payment);
        foreach($fields as $value) {
          $content = explode(': ', $value);
          if(isset($content[0]) and isset($content[1])) {
            $reply['payment'.$content[0]] = $content[1];
          }
        }
      }
    }
    return (count($reply)?$reply:null);
  }

}
