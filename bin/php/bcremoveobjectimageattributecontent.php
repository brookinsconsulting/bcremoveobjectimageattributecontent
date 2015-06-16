#!/usr/bin/env php
<?php
/**
 * File containing the bcremoveobjectimageattributecontent.php bin script
 *
 * @copyright Copyright (C) 1999 - 2016 Brookins Consulting. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or later)
 * @version 0.7.0
 * @package bcremoveobjectimageattributecontent
 */

/** Add a starting timing point tracking script execution time **/

$srcStartTime = microtime( true );

/** Script autoloads initialization **/

require 'autoload.php';

/** Script startup and initialization **/

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "BC Remove Content Object Image Attribute Content Script\n" .
                                                        "\n" .
                                                        "bcremoveobjectimageattributecontent.php --script-verbose" ),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true,
                                     'user' => true ) );

$script->startup();

$options = $script->getOptions( "[script-verbose;][script-verbose-level;][object-ids;][attribute-identifiers;][version;][test-only;][sql;]",
                                "[node]",
                                array( 'object-ids' => 'Use this parameter specify the objectIDs to remove the image attribute content. Parameter input is a comma separted list of objectIDs. Example: ' . "'--object-ids=42,75,101'" . ' is a required parameter',
                                       'attribute-identifiers' => 'Use this parameter specify the object class image attribute identifier(s) to remove the image attribute content from. Parameter input is a comma separted list of objectIDs. Example: ' . "'--attribute-identifiers=image|category_image|profile_image'" . ' is an optional parameter which defaults to ' ."'image'",
                                       'version' => 'Use this parameter to specify whether to remove image attribute content from the current version, in a new version or all versions. Parameter input is one of the following possible values: ' . "'current|new|all'. Example: " . "'--version=current'" . ' is an optional parameter which defaults to current',
                                       'script-verbose' => 'Use this parameter to display verbose script output without disabling script iteration counting of images created or removed. Example: ' . "'--script-verbose'" . ' is an optional parameter which defaults to false',
                                       'script-verbose-level' => 'Use only with ' . "'--script-verbose'" . ' parameter to see more of execution internals. Example: ' . "'--script-verbose-level=3'" . ' is an optional parameter which defaults to 1 and works till 5',
                                       'test-only' => 'Use this parameter to test for objects which need lat/lng attributes to be swapped. Test only no modifications to db made. Example: ' . "'--test-only'" . ' is an optional parameter which defaults to false',
                                       'sql' => "Use this parameter to display the sql queries executed by this script. Used for advanced debugging only. Requires use of the '-d' parameter to actually display sql queries. Example: " . "'--sql -d'" . ' is an optional parameter which defaults to false' ),
                                false,
                                array( 'user' => true ) );
$script->initialize();

/** Display of execution time **/
function executionTimeDisplay( $srcStartTime, $cli )
{
    /** Add a stoping timing point tracking and calculating total script execution time **/

    $srcStopTime = microtime( true );
    $startTimeCalc = $srcStartTime;
    $stopTimeCalc = $srcStopTime;
    $executionTime = round( $srcStopTime - $srcStartTime, 2 );

    /** Alert the user to how long the script execution took place **/

    $cli->output( "This script execution completed in " . $executionTime . " seconds" . ".\n" );
}

/** Test for required script arguments **/

$verbose = isset( $options['script-verbose'] ) ? true : false;

$scriptVerboseLevel = isset( $options['script-verbose-level'] ) ? $options['script-verbose-level'] : 1;

$troubleshoot = ( isset( $options['script-verbose-level'] ) && $options['script-verbose-level'] > 0 ) ? true : false;

$objectIDs = ( isset( $options['object-ids'] ) && strlen( $options['object-ids'] ) >= 1 ) ? explode( ',', $options['object-ids'] ) : false;

$attributeIdentifiers = ( isset( $options['attribute-identifiers'] ) && strlen( $options['attribute-identifiers'] ) >= 1 ) ? explode( ',', $options['attribute-identifiers'] ) : array( 'image' );

$version = ( isset( $options['version'] ) && strlen( $options['version'] ) >= 1 ) ? $options['version'] : 'current';

$test = isset( $options['test-only'] ) ? true : false;

$showSQL = isset( $options['sql'] ) ? true : false;

/** Script default values **/

$adminUserID = 14;
$objectVersionsModified = array();

$offset = 0;
$limit = 1;
$status = true;
$resultCounter = 1;
$asObject = true;

$contentObjectDefinition = eZContentObject::definition();
$contentObjectVersionDefinition = eZContentObjectVersion::definition();
$contentObjectDefinitionContentObjectID = $contentObjectDefinition['name'] . '.id';
$contentObjectVersionDefinitionContentObjectID = $contentObjectVersionDefinition['name'] . '.id';
$conditions = null;
$customConds = ' WHERE ';
$countCustomConds = $customConds;

if( $version == 'current' || $version == 'new' )
{
    $queryContentObjectXDefinition = $contentObjectDefinition;
    $queryContentObjectXDefinitionContentObjectID = $contentObjectDefinitionContentObjectID;
    $objectTypeName = 'objects';
    $objectTypeVersionName = "$version version";
}
elseif( $version == 'all' )
{
    $queryContentObjectXDefinition = $contentObjectVersionDefinition;
    $contentObjectVersionDefinitionContentObjectIDFieldName = str_replace( 'ez', '', $contentObjectDefinition['name'] );
    $queryContentObjectXDefinitionContentObjectID = $contentObjectVersionDefinition['name'] . "." . $contentObjectVersionDefinitionContentObjectIDFieldName . '_id';
    $objectTypeName = 'object versions';
    $objectTypeVersionName = "$version versions";
}

foreach( $objectIDs as $key => $sqlQueryContentObjectID )
{
    if( $key == 0 )
    {
        $countCustomConds .= $queryContentObjectXDefinitionContentObjectID . ' = ' . $sqlQueryContentObjectID;
        $customConds .= $contentObjectDefinitionContentObjectID . ' = ' . $sqlQueryContentObjectID;
    }
    else
    {
        $countCustomConds .= ' OR ' . $queryContentObjectXDefinitionContentObjectID . ' = ' . $sqlQueryContentObjectID;
        $customConds .= ' OR ' . $contentObjectDefinitionContentObjectID . ' = ' . $sqlQueryContentObjectID;
    }
}

/** Test for required object-ids argument **/

if ( !$objectIDs )
{
    $cli->error( "The --object-ids parameter is a required argument. Example parameter usage: --object-ids=42  OR  --object-ids=42,75,100,404" );
    $cli->output();
    // Shutdown the script and exit eZ
    $script->shutdown( 1 );
}

/** Login script to run as admin user  This is required to see past content tree permissions, sections and other limitations **/

$currentuser = eZUser::currentUser();
$currentuser->logoutCurrent();
$user = eZUser::fetch( $adminUserID );
$user->loginCurrent();

/** Enable sql query debug output **/

$db = eZDB::instance();
$db->setIsSQLOutputEnabled( $showSQL );

/** Fetch the count of the total number of actual matching objects from the database **/

$customFields = array( array( 'operation' => 'COUNT( * )', 'name' => 'row_count' ) );

$db->setIsSQLOutputEnabled( true );
$resultCountResult = eZPersistentObject::fetchObjectList( $queryContentObjectXDefinition,
                                                          array(), $conditions, array(), null,
                                                          false, false, $customFields, null, $countCustomConds );

$resultCount = $resultCountResult[0]['row_count'];

/** Debug verbose output **/

if ( !$resultCount )
{
    $cli->error( "No matching object ids found" );

    /** Call for display of execution time **/
    executionTimeDisplay( $srcStartTime, $cli );

    $script->shutdown( 3 );
}
elseif( $verbose && $resultCount > 0 )
{
    $cli->warning( "Total $objectTypeName $objectTypeVersionName to be modified: " . $resultCount . "\n" );
}

/** Setup script iteration details **/

$script->setIterationData( '.', '.' );
$script->resetIteration( $resultCount );

/** Iterate over count of objects **/

$customFields = null;

while ( $offset < $resultCount )
{
    /** Optional debug output **/

    if( $troubleshoot && $scriptVerboseLevel >= 5 )
    {
        $cli->output( "Top of offset iteration. Offset: $offset\n");
    }

    /** Fetch content objects with limit and offset **/

    $limitCond = array( 'offset' => $offset, 'length' => $limit );

    if( $version == 'all' )
    {
        $objects = eZPersistentObject::fetchObjectList( $queryContentObjectXDefinition,
                                                        null, $conditions, null, $limitCond,
                                                        $asObject, false, $customFields, null, $countCustomConds );
    }
    else
    {
        $objects = eZPersistentObject::fetchObjectList( $contentObjectDefinition,
                                                        null, $conditions, null, $limitCond,
                                                        $asObject, false, $customFields, null, $customConds );
    }

    $objectsCount = count( $objects );

    /** Iterate over objects **/
    while ( list( $objectKey, $object ) = each( $objects ) )
    {
        $foundMatchingObjectAttributeWithContent = false;
        $updateResult = false;

        if( $version == 'current' || $version == 'new' )
        {
            $objectID = $object->attribute( 'id' );
        }
        elseif( $version == 'all' )
        {
            $objectID = $object->attribute('contentobject')->attribute( 'id' );
            $objectVersionID = $object->attribute( 'id' );
        }

        /** Optional debug output **/

        if( $troubleshoot && $scriptVerboseLevel >= 4 )
        {
            if( $resultCounter != 1 )
            {
                $cli->output();
            }
            $cli->output( "Evaluating Object Content. ObjectID: $objectID\n");
        }

        if( $version == 'current' || $version == 'new' )
        {
            $objectCurrentVersion = $object->attribute( 'current_version' );
            $objectVersionArray = array( $object );
        }
        elseif( $version == 'all' )
        {
            $objectCurrentVersion = $object->attribute( 'version' );
            $objectVersionArray = array( $object );
        }

        while ( list( $objectVersionKey, $objectVersion ) = each( $objectVersionArray ) )
        {
            $objectVersionAttributeIdentifiers = $attributeIdentifiers;
            $foundMatchingObjectAttributeWithContent = false;
            $db->begin();

            if( $version == 'current' || $version == 'new' )
            {
                $objectVersionNumber = $objectVersion->attribute( 'current_version' );
            }
            elseif( $version == 'all')
            {
                $objectVersionNumber = $objectVersion->attribute( 'version' );
            }

            /** Optional debug output **/

            if( $troubleshoot && $scriptVerboseLevel >= 4 )
            {
                $cli->output( "Evaluating Object Version Content. VersionID: $objectVersionNumber\n");
            }

            $objectVersionDataMap = $objectVersion->attribute( 'data_map' );

            while ( list( $attributeIdentifierKey, $attributeIdentifier ) = each( $objectVersionAttributeIdentifiers ) )
            {
                if ( isset( $objectVersionDataMap[ $attributeIdentifier ] ) )
                {
                    $objectVersionImageAttribute = $objectVersionDataMap[ $attributeIdentifier ];
                    $objectVersionImageAttributeID = $objectVersionImageAttribute->attribute( 'id' );

                    /** Optional debug output **/

                    if( $troubleshoot && $scriptVerboseLevel >= 4 )
                    {
                        $cli->output( "Content object version has attribute. Attribute identifier: $attributeIdentifier\n");
                    }

                    if ( $objectVersionImageAttribute->attribute('has_content') )
                    {
                        $foundMatchingObjectAttributeWithContent = true;

                        /** Optional debug output **/

                        if( $troubleshoot && $scriptVerboseLevel >= 4 )
                        {
                            $cli->output( "Content object version attribute has content\n");
                        }

                        if( !$test && $version == 'new' )
                        {
                            $newVersion = $object->createNewVersion( false, true );
                            $objectVersionNumber = $newVersion->attribute( 'version' );
                            $objectVersionDataMap = $newVersion->attribute( 'data_map' );
                            $objectVersionImageAttribute = $objectVersionDataMap[ $attributeIdentifier ];
                            $objectVersionImageAttributeID = $objectVersionImageAttribute->attribute( 'id' );
                        }

                        if( !$test )
                        {
                            $objectVersionsModified[] = array( 'objectID' => $objectID, 'version' => $objectVersionNumber, 'AttributeID' => $objectVersionImageAttributeID );
                            $objectVersionImageDataType = $objectVersionImageAttribute->dataType();

                            // Note: That for best possible BC of all older versions of eZ Publish Legacy (Re: eZp version <= 4.7 (and possibly beyond)) we *MUST* pass some non-null parameter content
                            // to the second paramenter of the following method call as we found the hard way, ezp4.7 required this parameter to function correctly while ezp2014.11 did not even test
                            // for a second parameter to function correctly.
                            $objectVersionImageDataType->deleteStoredObjectAttribute( $objectVersionImageAttribute, $objectCurrentVersion );

                            /** Optional debug output **/

                            if( $troubleshoot && $scriptVerboseLevel >= 4 )
                            {
                                $cli->output( "Content object version attribute content removed!\n");
                            }
                        }
                    }
                    else
                    {
                        /** Optional debug output **/

                        if( $troubleshoot && $scriptVerboseLevel >= 6 )
                        {
                            $cli->output( "Content object version attribute does not have any content:\n");
                            print_r( $objectVersionImageAttribute->attribute('data_text') ); echo "\n";
                        }
                    }
                }
            }

            if( $version == 'new' && $foundMatchingObjectAttributeWithContent && !$test )
            {
                $result = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $objectID, 'version' => $objectVersionNumber ) );

                /** Optional debug output **/

                if( $result['status'] == 1 && $troubleshoot && $scriptVerboseLevel >= 3 )
                {
                    $cli->output( "New version of modified content object published!");
                }
            }

            $db->commit();

            $script->iterate( $cli, $status );
        }

        /** Clear all related caches **/
        eZContentCacheManager::clearContentCacheIfNeeded( $objectID );
    }

    /** Optional debug output **/

    if( $troubleshoot && $scriptVerboseLevel >= 5 )
    {
        $cli->output( "End of offset iteration. Offset: " . $offset . ", Object Count: " . $objectsCount . ", Counter: $resultCounter\n" );
    }

    /** Iterate fetch function offset and continue **/
    $offset = $offset + $objectsCount;
    $resultCounter++;
}



/** Exit script execution test user alert **/

$objectVersionsModifiedCount = count( $objectVersionsModified );

if( $objectVersionsModifiedCount > 0 && $troubleshoot && $scriptVerboseLevel >= 3 )
{
    if( $scriptVerboseLevel < 5)
        $cli->output();

    $cli->warning( "Number of content object versions modified: ", false );
    $cli->output( $objectVersionsModifiedCount . "\n" );

    $cli->warning( "Report of content $objectTypeName modified: \n" );

    foreach( $objectVersionsModified as $objectVersionModifiedKey => $objectVersionModified )
    {
        $cli->output( "ObjectID: " . $objectVersionModified['objectID'] );
        $cli->output( "ObjectVersion: " . $objectVersionModified['version'] );
        $cli->output( "AttributeID: " . $objectVersionModified['AttributeID'] );

        if( isset( $objectVersionsModified[ ($objectVersionModifiedKey + 1) ] ) )
        {
            $cli->output();
        }
    }
    $cli->output();
}
else
{
    $cli->warning( "\nNo content object's image attribute content modified!\n" );

}

/** Call for display of execution time **/
executionTimeDisplay( $srcStartTime, $cli );

/** Shutdown script **/
$script->shutdown();

?>