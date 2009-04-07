<?php
/**
 * Lets the user import an XML file to turn into wiki pages
 *
 * @author Yaron Koren
 */

if (!defined('MEDIAWIKI')) die();

class DTImportXML extends SpecialPage {

	/**
	 * Constructor
	 */
	public function DTImportXML() {
		global $wgLanguageCode;
		SpecialPage::SpecialPage('ImportXML');
		dtfInitContentLanguage($wgLanguageCode);
		wfLoadExtensionMessages('DataTransfer');
	}

	function execute($query = '') {
		global $wgUser, $wgOut, $wgRequest;
		$this->setHeaders();

		if ( ! $wgUser->isAllowed('importxml') ) {
			global $wgOut;
			$wgOut->permissionRequired('importxml');
			return;
		}

		if ($wgRequest->getCheck('import_file')) {
			$text = "<p>" . wfMsg('dt_importxml_importing') . "</p>\n";
			$source = ImportStreamSource::newFromUpload( "xml_file" );
			$text .= self::modifyPages($source);
		} else {
			$select_file_label = wfMsg('dt_importxml_selectfile');
			$import_button = wfMsg('import-interwiki-submit');
			$text =<<<END
	<p>$select_file_label</p>
	<form enctype="multipart/form-data" action="" method="post">
	<p><input type="file" name="xml_file" size="25" /></p>
	<p><input type="Submit" name="import_file" value="$import_button"></p>
	</form>

END;
		}

		$wgOut->addHTML($text);
	}

	function modifyPages($source) {
		$text = "";
		$xml_parser = new DTXMLParser( $source );
		$xml_parser->doParse();
		$jobs = array();
		$job_params = array();
		global $wgUser;
		$job_params['user_id'] = $wgUser->getId();
		$job_params['edit_summary'] = wfMsgForContent('dt_importxml_editsummary');

		foreach ($xml_parser->mPages as $page) {
			$title = Title::newFromText($page->getName());
			$job_params['text'] = $page->createText();
			$jobs[] = new DTImportJob( $title, $job_params );
			//$text .= "<p>{$page->getName()}:</p>\n";
			//$text .= "<pre>{$page->createText()}</pre>\n";
		}
		Job::batchInsert( $jobs );
		$text .= wfMsg('dt_importxml_success', count($jobs));
		return $text;
	}

}