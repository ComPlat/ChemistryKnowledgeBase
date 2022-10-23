<?php

namespace DIQA\Util\ParserFunctions;

use Parser;
/**
 * Class that provides the {{#isUserMemberOfGroup:}} parser function.
 *
 * The parser function has one arguments. It represents the name of the
 * group to check for.
 * The parser function returns TRUE if the current user belongs to the
 * passed group. Otherwise it returns FALSE.
 * If no argument is passed the return value will be a comma-separated list
 * of all groups the user belongs to.
 *
 * Testpage:
	;isUserMemberOfGroup
	* isUserMemberOfGroup(inventarisation)={{#isUserMemberOfGroup: inventarisation}}
	* isUserMemberOfGroup(odbpoweruser)={{#isUserMemberOfGroup: odbpoweruser}}
	* isUserMemberOfGroup(smwadministrator)={{#isUserMemberOfGroup: smwadministrator}}
	* isUserMemberOfGroup(sysop)={{#isUserMemberOfGroup: sysop}}
	* isUserMemberOfGroup(bureaucrat)={{#isUserMemberOfGroup: bureaucrat}}
	* isUserMemberOfGroup(bot)={{#isUserMemberOfGroup: bot}}
	* isUserMemberOfGroup(QQQ)={{#isUserMemberOfGroup: QQQ}}
	* isUserMemberOfGroup = {{#isUserMemberOfGroup: }}
 *
 * @author Michael Erdmann
 */
class IsUserMemberOfGroup {

	static function isUserMemberOfGroup( Parser $parser, $groupName ) {
		//$parser->disableCache ();

		if(is_null($groupName)) {
			$output = "FALSE";

		} else {
			$groupName = trim($groupName);
			if($groupName == "") {
				$currentUser = $parser->getUser();
				$currentUserGroups = $currentUser->getGroups();
				$output = implode(",", $currentUserGroups);

			} else {
				$currentUser = $parser->getUser();
				$currentUserGroups = $currentUser->getGroups();
				if(in_array($groupName, $currentUserGroups)) {
					$output = "TRUE";
				} else {
					$output = "FALSE";
				}
			}
		}

		return array( $output, 'noparse' => true );
	}

	/**
	 * Registers parser hook for MW.
	 *
	 * @param Parser $parser
	 */
	public static function registerParserHooks(Parser &$parser) {
	    $parser->setFunctionHook('isUserMemberOfGroup', 'DIQA\Util\ParserFunctions\IsUserMemberOfGroup::isUserMemberOfGroup');
	}
	
	
}
