<?
defined('C5_EXECUTE') or die("Access Denied.");
$form = Loader::helper('form');
$cParentID = false;
if (is_object($page)) {
	$cParentID = $page->getPageTargetParentPageID();
}
if (is_object($pagetype) && $pagetype->getPageTypePublishTargetTypeID() == $this->getPageTypePublishTargetTypeID()) {
	$ps = Loader::helper('form/page_selector');
	print $ps->selectPage('cParentID', $cParentID);
}