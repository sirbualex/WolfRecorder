<?php

/**
 * Camera devices management
 */
class Cameras {

    /**
     * Contains alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Cameras database abstraction layer placeholder
     *
     * @var object
     */
    protected $camerasDb = '';

    /**
     * Contains all available cameras as id=>cameraData
     *
     * @var array
     */
    protected $allCameras = array();

    /**
     * Camera models instnce placeholder
     * 
     * @var object
     */
    protected $models = '';

    /**
     * Storages instance placeholder.
     *
     * @var object
     */
    protected $storages = '';

    /**
     * System messages helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * some predefined stuff here
     */
    const DATA_TABLE = 'cameras';
    const URL_ME = '?module=cameras';
    const PROUTE_NEWMODEL = 'newcameramodelid';
    const PROUTE_NEWIP = 'newcameraip';
    const PROUTE_NEWLOGIN = 'newcameralogin';
    const PROUTE_NEWPASS = 'newcamerapassword';
    const PROUTE_NEWACT = 'newcameraactive';
    const PROUTE_NEWSTORAGE = 'newcamerastorageid';
    const PROUTE_NEWCOMMENT = 'newcameracomment';
    const ROUTE_DEL = 'deletecameraid';
    const ROUTE_EDIT = 'editcameraid';
    const ROUTE_ACTIVATE = 'activatecameraid';
    const ROUTE_DEACTIVATE = 'deactivatecameraid';

    /**
     * Dinosaurs are my best friends
     * Through thick and thin, until the very end
     * People tell me, do not pretend
     * Stop living in your made up world again
     * But the dinosaurs, they're real to me
     * They bring me up and make me happy
     * I wish that the world could see
     * The dinosaurs are a part of me
     */
    public function __construct() {
        $this->initMessages();
        $this->loadConfigs();
        $this->initCamerasDb();
        $this->initStorages();
        $this->initModels();
        $this->loadAllCameras();
    }

    /**
     * Inits system messages helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads all required configs
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Inits storages into protected prop for further usage
     * 
     * @return void
     */
    protected function initStorages() {
        $this->storages = new Storages();
    }

    /**
     * Inits camera models in protected prop
     * 
     * @return void
     */
    protected function initModels() {
        $this->models = new Models();
    }

    /**
     * Inits database abstraction layer for further usage
     * 
     * @return void
     */
    protected function initCamerasDb() {
        $this->camerasDb = new NyanORM(self::DATA_TABLE);
    }

    /**
     * Loads all existing cameras from database
     * 
     * @return void
     */
    protected function loadAllCameras() {
        $this->camerasDb->orderBy('id', 'DESC');
        $this->allCameras = $this->camerasDb->getAll('id');
    }

    /**
     * Returns camera creation form
     * 
     * @return string
     */
    public function renderCreateForm() {
        $result = '';
        $allStorages = $this->storages->getAllStorageNames();
        $allModels = $this->models->getAllModelNames();
        if (!empty($allStorages)) {
            $storagesParams = array();
            foreach ($allStorages as $eachStorageId => $eachStorageName) {
                $storagesParams[$eachStorageId] = __($eachStorageName);
            }
            if (!empty($allModels)) {
                $inputs = wf_Selector(self::PROUTE_NEWMODEL, $allModels, __('Model'), '', false) . ' ';
                $inputs .= wf_TextInput(self::PROUTE_NEWIP, __('IP'), '', false, 12, 'ip') . ' ';
                $inputs .= wf_TextInput(self::PROUTE_NEWLOGIN, __('Login'), '', false, 8, 'alphanumeric') . ' ';
                $inputs .= wf_TextInput(self::PROUTE_NEWPASS, __('Password'), '', false, 8, '') . ' ';
                $inputs .= wf_CheckInput(self::PROUTE_NEWACT, __('Enabled'), false, true) . ' ';
                $inputs .= wf_Selector(self::PROUTE_NEWSTORAGE, $storagesParams, __('Storage'), '', false) . ' ';
                $inputs .= wf_TextInput(self::PROUTE_NEWCOMMENT, __('Description'), '', false, 10, '') . ' ';
                $inputs .= wf_Submit(__('Create'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result .= $this->messages->getStyledMessage(__('Any device models exists'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Any storages exists'), 'error');
        }
        return($result);
    }

    /**
     * Returns unique channelId
     * 
     * @return string
     */
    protected function getChannelId() {
        $result = '';
        $busyCnannelIds = array();
        if (!empty($this->allCameras)) {
            foreach ($this->allCameras as $io => $each) {
                $busyCnannelIds[$each['channel']] = $each['id'];
            }
        }

        $result = zb_rand_string(8);
        while (isset($busyCnannelIds[$result])) {
            $result = zb_rand_string(8);
        }

        return($result);
    }

    /**
     * Creates new camera
     * 
     * @param int $modelId
     * @param string $ip
     * @param string $login
     * @param string $password
     * @param bool $active
     * @param int $storageId
     * @param comment $comment
     * 
     * @return void/string on error
     */
    public function create($modelId, $ip, $login, $password, $active, $storageId, $comment = '') {
        $result = '';
        $modelId = ubRouting::filters($modelId, 'int');
        $ipF = ubRouting::filters($ip, 'mres');
        $loginF = ubRouting::filters($login, 'mres');
        $passwordF = ubRouting::filters($password, 'mres');
        $actF = ($active) ? 1 : 0;
        $storageId = ubRouting::filters($storageId, 'int');
        $commentF = ubRouting::filters($comment, 'mres');
        $channelId = $this->getChannelId();

        $allStorages = $this->storages->getAllStorageNames();
        $allModels = $this->models->getAllModelNames();
        if (isset($allStorages[$storageId])) {
            $storageData = $this->storages->getStorageData($storageId);
            $storagePathValid = $this->storages->checkPath($storageData['path']);
            if ($storagePathValid) {
                if (isset($allModels[$modelId])) {
                    if (zb_isIPValid($ipF)) {
                        if (!empty($loginF) AND ! empty($passwordF)) {
                            $this->camerasDb->data('modelid', $modelId);
                            $this->camerasDb->data('ip', $ipF);
                            $this->camerasDb->data('login', $loginF);
                            $this->camerasDb->data('password', $passwordF);
                            $this->camerasDb->data('active', $actF);
                            $this->camerasDb->data('storageid', $storageId);
                            $this->camerasDb->data('channel', $channelId);
                            $this->camerasDb->data('comment', $commentF);
                            $this->camerasDb->create();
                            $newId = $this->camerasDb->getLastId();
                            log_register('CAMERA CREATE [' . $newId . ']  MODEL [' . $modelId . '] IP `' . $ip . '` STORAGE [' . $storageId . ']');
                        } else {
                            $result .= __('Login or password is empty');
                        }
                    } else {
                        $result .= __('Wrong IP format') . ': `' . $ip . '`';
                    }
                } else {
                    $result .= __('Storage path is not writable');
                }
            } else {
                $result .= __('Model') . ' [' . $modelId . '] ' . __('not exists');
            }
        } else {
            $result .= __('Storage') . ' [' . $storageId . '] ' . __('not exists');
        }
        return($result);
    }

    /**
     * Renders available cameras list
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        if (!empty($this->allCameras)) {
            $allModels = $this->models->getAllModelNames();

            $starDust = new StarDust();

            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Model'));
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('Enabled'));
            $cells .= wf_TableCell(__('Recording'));
            $cells .= wf_TableCell(__('Description'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allCameras as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($allModels[$each['modelid']]);
                $cells .= wf_TableCell($each['ip']);
                $cells .= wf_TableCell(web_bool_led($each['active']));
                $starDust->setProcess(Recorder::PID_PREFIX . $each['id']);
                $recordingFlag = $starDust->isRunning();
                $cells .= wf_TableCell(web_bool_led($recordingFlag));
                $cells .= wf_TableCell($each['comment']);
                $deletionUrl = self::URL_ME . '&' . self::ROUTE_DEL . '=' . $each['id'];
                $cancelUrl = self::URL_ME;
                $deletionAlert = $this->messages->getDeleteAlert() . '. ' . wf_tag('br');
                $deletionAlert .= __('Also all archive data for this camera will be destroyed permanently') . '.';
                $deletionTitle = __('Delete') . ' ' . __('Camera') . ' ' . $each['ip'] . '?';
                $actLinks = wf_ConfirmDialog($deletionUrl, web_delete_icon(), $deletionAlert, '', $cancelUrl, $deletionTitle) . ' ';
                $actLinks .= wf_Link(self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $each['id'], web_edit_icon(), false);
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable resp-table');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return($result);
    }

    /**
     * Deletes existing camera from database
     * 
     * @param int $cameraId
     * 
     * @return void/string on error
     */
    public function delete($cameraId) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        //TODO: do something around camera deactivation and checks for running recording
        if (isset($this->allCameras[$cameraId])) {
            $cameraData = $this->allCameras[$cameraId];
            if ($cameraData['active'] == 0) {
                $this->camerasDb->where('id', '=', $cameraId);
                $this->camerasDb->delete();
                log_register('CAMERA DELETE [' . $cameraId . ']');
                //flushing camera channel
                $this->storages->flushChannel($cameraData['storageid'], $cameraData['channel']);
            } else {
                $result .= __('You cant delete camera which is now active');
            }
        } else {
            $result .= __('Camera not exists') . ' [' . $cameraId . ']';
        }

        return($result);
    }

    /**
     * Returns full cameras data with all info required for recorder as struct id=>[CAMERA,TEMPLATE,STORAGE]
     * 
     * @return array
     */
    public function getAllCamerasFullData() {
        $result = array();
        if (!empty($this->allCameras)) {
            $allModelsTemplates = $this->models->getAllModelTemplates();
            $allStoragesData = $this->storages->getAllStoragesData();
            foreach ($this->allCameras as $io => $each) {
                $result[$each['id']]['CAMERA'] = $each;
                $result[$each['id']]['TEMPLATE'] = $allModelsTemplates[$each['modelid']];
                $result[$each['id']]['STORAGE'] = $allStoragesData[$each['storageid']];
            }
        }
        return($result);
    }

    /**
     * Renders camera editing interface
     * 
     * @param int $cameraId
     * 
     * @return string
     */
    public function renderEditForm($cameraId) {
        $result = '';
        $cameraControls = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCameras[$cameraId])) {
            $allModels = $this->models->getAllModelNames();
            $allStorages = $this->storages->getAllStorageNames();
            $cameraData = $this->allCameras[$cameraId];

            //recorder process now is running?
            $starDust = new StarDust();
            $starDust->setProcess(Recorder::PID_PREFIX . $cameraData['id']);
            $recordingFlag = $starDust->isRunning();

            //some channel data collecting
            $channelChunks = $this->storages->getChannelChunks($cameraData['storageid'], $cameraData['channel']);
            $chunksCount = sizeof($channelChunks);
            $archiveDepth = '-';
            $archiveSeconds = 0;
            if ($chunksCount > 0) {
                $archiveSeconds = $this->altCfg['RECORDER_CHUNK_TIME'] * $chunksCount;
                $archiveDepth = wr_formatTimeArchive($archiveSeconds);
            }

            $chanSizeRaw = $this->storages->getChannelSize($cameraData['storageid'], $cameraData['channel']);
            $chanSizeLabel = wr_convertSize($chanSizeRaw);

            $chanBitrateLabel = '-';
            if ($archiveSeconds AND $chanSizeRaw) {
                $chanBitrate = ($chanSizeRaw * 8) / $archiveSeconds / 1024; // in kbits
                $chanBitrateLabel = round(($chanBitrate / 1024), 2) . ' ' . __('Mbit/s');
            }

            //camera profile here
            $cells = wf_TableCell(__('Model'), '40%', 'row2');
            $cells .= wf_TableCell($allModels[$cameraData['modelid']]);
            $rows = wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('IP'), '', 'row2');
            $cells .= wf_TableCell($cameraData['ip']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Login'), '', 'row2');
            $cells .= wf_TableCell($cameraData['login']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Password'), '', 'row2');
            $cells .= wf_TableCell($cameraData['password']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Enabled'), '', 'row2');
            $cells .= wf_TableCell(web_bool_led($cameraData['active']));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Recording'), '', 'row2');
            $cells .= wf_TableCell(web_bool_led($recordingFlag));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Description'), '', 'row2');
            $cells .= wf_TableCell($cameraData['comment']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Storage'), '', 'row2');
            $cells .= wf_TableCell(__($allStorages[$cameraData['storageid']]));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Channel'), '', 'row2');
            $cells .= wf_TableCell($cameraData['channel']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Archive depth'), '', 'row2');
            $cells .= wf_TableCell($archiveDepth);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Bitrate'), '', 'row2');
            $cells .= wf_TableCell($chanBitrateLabel);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Size'), '', 'row2');
            $cells .= wf_TableCell($chanSizeLabel);
            $rows .= wf_TableRow($cells, 'row3');



            $result .= wf_TableBody($rows, '100%', 0, 'resp-table');

            //some controls here
            if ($cameraData['active']) {
                $deactUrl = self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $cameraData['id'] . '&' . self::ROUTE_DEACTIVATE . '=' . $cameraData['id'];
                $cameraControls .= wf_Link($deactUrl, web_bool_led(0) . ' ' . __('Disable'), false, 'ubButton') . ' ';
            } else {
                $cameraControls .= wf_Link(self::URL_ME . '&' . self::ROUTE_ACTIVATE . '=' . $cameraData['id'], web_bool_led(1) . ' ' . __('Enable'), false, 'ubButton') . ' ';
            }
            if (cfr('ARCHIVE')) {
                $cameraControls .= wf_Link(Archive::URL_ME . '&' . Archive::ROUTE_VIEW . '=' . $cameraData['id'], wf_img('skins/icon_archive_small.png') . ' ' . __('Archive'), false, 'ubButton');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Camera') . ' [' . $cameraId . '] ' . __('not exists'), 'error');
        }


        $result .= wf_delimiter(0);
        $result .= wf_BackLink(self::URL_ME) . ' ';
        $result .= $cameraControls;
        return($result);
    }

    /**
     * Shutdown camera to unlock its settings
     * 
     * @param int $cameraId
     * 
     * @return void
     */
    public function deactivate($cameraId) {
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCameras[$cameraId])) {
            $recorder = new Recorder();
            //shutdown recording process
            $recorder->stopRecord($cameraId); //this method locks execution until capture process will be really killed
            //disabling camera activity flag
            $this->camerasDb->where('id', '=', $cameraId);
            $this->camerasDb->data('active', 0);
            $this->camerasDb->save();
            log_register('CAMERA DEACTIVATE [' . $cameraId . ']');
        }
    }

    /**
     * Enables camera to lock its settings
     * 
     * @param int $cameraId
     * 
     * @return void
     */
    public function activate($cameraId) {
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCameras[$cameraId])) {
            //enabling camera activity flag
            $this->camerasDb->where('id', '=', $cameraId);
            $this->camerasDb->data('active', 1);
            $this->camerasDb->save();
            $this->allCameras[$cameraId]['active'] = 1;
            log_register('CAMERA ACTIVATE [' . $cameraId . ']');

            //starting capture now if enabled
            if ($this->altCfg['RECORDER_ON_CAMERA_ACTIVATION']) {
                $recorder = new Recorder();
                $recorder->runRecordBackground($cameraId);
            }
        }
    }

}
