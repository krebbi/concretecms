<?
defined('C5_EXECUTE') or die("Access Denied.");
class Concrete5_Controller_Dialogs_Page_AddBlock extends BackendInterfacePageController {

	protected $viewPath = '/system/dialogs/page/add_block';

	protected function canAccess() {
		return $this->areaPermissions->canAddBlock($this->blockType);
	}

	public function __construct() {
		parent::__construct();
		$request = $this->request;

		if (!Loader::helper('validation/numbers')->integer($_REQUEST['btID'])) {
			throw new Exception(t('Access Denied'));
		}

		$this->blockType = BlockType::getByID($_REQUEST['btID']);
		$this->area = Area::get($this->page, $_REQUEST['arHandle']);
		$this->pageToModify = $this->page;
		$this->areaToModify = $this->area;
		if ($this->area->isGlobalArea()) {
			$this->pageToModify = Stack::getByName($_REQUEST['arHandle']);
			$this->areaToModify = Area::get($this->page, STACKS_AREA_NAME);
		}
		$this->areaPermissions = new Permissions($this->areaToModify);
		$cnt = $this->blockType->getController();
		if (!is_a($cnt, 'BlockController')) {
			throw new Exception(t('Unable to load the controller for this block type. Perhaps it has been moved or removed.'));
		}
		$this->blockTypeController = $cnt;
	}

	public function view() {
		$bv = new BlockView($this->blockType);
		$bv->setAreaObject($this->area);
		// Handle special posted area parameters here
		if (isset($_REQUEST['arGridColumnSpan'])) {
			$this->area->setAreaGridColumnSpan(intval($_REQUEST['arGridColumnSpan']));
		}
		$bv->addScopeItems(array('a' => $this->a, 'cp' => $this->permissions, 'ap' => $this->areaPermissions));
		$this->set('blockView', $bv);
		$this->set('blockType', $this->blockType);
		$this->set('btHandle', $this->blockType->getBlockTypeHandle());
		$this->set("blockTypeController", $this->blockTypeController);
		$this->set('area', $this->area);
	}

	public function submit() {
		$pc = new PageEditResponse($this->error);
		$pc->setPage($this->page);
		if ($this->validateAction()) {

			$data = $_POST;
			$bt = $this->blockType;
			$u = new User();
			$data['uID'] = $u->getUserID();

			$e = $this->blockTypeController->validate($data);
			if ((!is_object($e)) || (($e instanceof ValidationErrorHelper) && (!$e->has()))) {

				if (!$bt->includeAll()) {
					$nvc = $this->pageToModify->getVersionToModify();
					$nb = $nvc->addBlock($bt, $this->areaToModify, $data);
				} else {
					// if we apply to all, then we don't worry about a new version of the page
					$nb = $cx->addBlock($bt, $this->areaToModify, $data);
				}

				if ($this->area->isGlobalArea() && $nvc instanceof Collection) {
					$xvc = $this->page->getVersionToModify(); // we need to create a new version of THIS page as well.
					$xvc->relateVersionEdits($nvc);
				}
				$pc->setAdditionalDataAttribute('btID', $nb->getBlockTypeID());
				$pc->setAdditionalDataAttribute('bID', $nb->getBlockID());
				$pc->setAdditionalDataAttribute('arHandle', $this->areaToModify->getAreaHandle());
				$pc->setAdditionalDataAttribute('aID', $this->areaToModify->getAreaID());
			} else {
				$pc->setError($e);
			}

		}
		$pc->outputJSON();
	}

}

