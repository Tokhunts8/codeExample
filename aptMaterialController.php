<?php

namespace CoreBundle\Controller;

class aptMaterialController extends ControllerBase {

    const SECTION_ARRAY_NAME = 'materials';
    const SECTION_NAME = 'material';
    const HII_SECTION_NAME = 'submission';

    protected $aptRepo = null;
    protected $user;

    /** @var CoreBundle\Model\MaterialManager * */
    protected $materialM;
    protected $materialRepo;

    protected function setup(Request $request, $getUser = true) {
        $this->aptRepo = $this->getAppointmentRepository();
        $this->materialRepo = $this->getManager()->getRepository('CoreBundle:AptMaterial');
        $this->user = ($getUser) ? $this->getCurrentUser() : null;
        $this->materialM = $this->getMaterialManager();
    }

    /**
     * @api {post} /api/apt/{aptid}/materials Add Appointment Material
     * 
     * @apiParam {uuid_string} aptid Appointment ID.
     * 
     * @apiName AptMaterialAdd
     * @apiVersion 2.0.0
     * @apiGroup Appointments
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse OneMaterialRequest
     * @apiUse oneMaterialResponse
     * @apiUse OneMaterialZapRequest
     * @apiUse OneMaterialAGRequest
     * @apiUse OneMaterialCrtRequest
     * @apiParamExample {json} Image Material:
     * {
     *     "material": {
     *         "type": "image",
     *         "title": "Untitled material",
     *         "description": null,
     *         "payload": {
     *             "uuid": "AmhVRGNULXYx"
     *         },
     *         "previewImage": null,
     *         "uuid": null
     *     }
     * }
     * @apiUse NotFoundError
     * 
     * @param Request $request
     * @param type $uuid
     * @return JSONResponse
     */
    public function addMaterialAction(Request $request, $uuid) {
        $this->setup($request);
        $aptO = $this->aptRepo->byUuid($uuid);
        $data = $this->getRequestData($request, self::SECTION_NAME);
        $material = $this->materialM->newMaterialFromData($data, $aptO, $this->user, TRUE, TRUE);
        return $this->oneMaterialResponse($material);
    }

    /**
     * @api {put} /api/apt/{aptid}/materials/{id} Edit Appointment Material
     * @apiDescription  Read-write fields: 'title', 'description', 'payload', 'previewimage' - for type=video
     * 
     * Read-only fields: 'uuid', 'createdAt', 'editedAt', 'payloadUrl'
     * 
     * Create-only field: 'type'
     * @apiParam {uuid_string} aptid Appointment ID.
     * @apiParam {uuid_string} id Material ID.
     * 
     * @apiName AptMaterialEdit
     * @apiVersion 2.0.0
     * @apiGroup Appointments
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse OneMaterialRequest
     * @apiParamExample {json} Request-body:
     * {
     *     "material": {
     *         "type": "image",
     *         "title": "Brains!",
     *         "description": "What do we want?",
     *         "payload": null,
     *         "previewImage": "//media.example.org/v/ad/ac/kb/1751909b7fe87062ead7af921c4aac24.jpg",
     *         "uuid": "7ee58145-407f-4021-91fb-415e60302b2a"
     *     }
     * }
     * @apiUse oneMaterialResponse
     * @apiUse NotFoundError
     */
    /* @param Request $request
     * @param type $uuid
     * @return JSONResponse
     */
    public function editMaterialAction(Request $request, $aptid, $uuid) {
        $this->setup($request);
        $aptO = $this->aptRepo->byUuid($aptid);
        $data = $this->getRequestData($request, self::SECTION_NAME);
        $material = $this->materialM->byUuidAndApt($uuid, $aptO);
        $edited = $this->materialM->editMaterial($data, $aptO, $material, $this->user, true);
        return $this->oneMaterialResponse($edited);
    }

    public function getMaterialAction(Request $request, $aptid, $uuid) {
        $this->setup($request);
        $aptO = $this->aptRepo->byUuid($aptid);
        $material = $this->materialM->byUuidAndApt($uuid, $aptO);
        return $this->oneMaterialResponse($material, self::SECTION_NAME, $this->user);
    }

    /**
     * @apiDefine oneSubmissionResponse
     * @apiSuccess (200) {object} submission Material Object
     * @apiSuccessExample {json} Response-Body:
     * {
     *     "submission": {
     *         "type": "image",
     *         "uuid": "7ee58145-407f-4021-91fb-415e60302b2a",
     *         "title": "Brains!",
     *         "description": "What do we want?",
     *         "payloadUrl": "\/\/media.example.org\/v\/ad\/ac\/kb\/1751909b7fe87062ead7af921c4aac24.jpg",
     *         "payload": null,
     *         "previewImage": "\/\/media.example.org\/v\/ad\/ac\/kb\/1751909b7fe87062ead7af921c4aac24.jpg",
     *         "authorId": "b782861e-34f8-11e3-90a9-8575bb483946",
     *         "authorName": "Author Name",
     *         "createdAt": "2015-10-23T09:06:11",
     *         "final": false,
     *         "editedAt": "2015-10-23T09:47:28"
     *     }
     * }
     * 
     * @return JSONResponse
     */
    protected function oneMaterialResponse($material, $sn = self::SECTION_NAME, $user = null) {
        if (null === $user) {
            $user = $this->getCurrentUser(true);
        }
        return new JSONResponse([$sn => $this->materialM->formatMaterialInfo($material, $user)]);
    }

    /**
     * @api {get} /api/apt/{aptid}/materials Get List of Appointment Materials.
     * 
     * @apiParam {uuid_string} aptid Appointment ID.
     * 
     * @apiName AptMaterialList
     * @apiVersion 2.0.0
     * @apiGroup Appointments
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiSuccess (200) {object[]} materials List of materials
     * @apiSuccessExample {json} Response-Body:
     * {
     *     "materials": [
     *         {
     *             "description": null,
     *             "payloadUrl": "#",
     *             "payload": "<p>Sample text<br><\/p>",
     *             "type": "text",
     *             "uuid": "238ce4c4-900d-44f2-87b2-dbd622f3e358",
     *             "title": "Untitled material",
     *             "previewImage": null,
     *             "createdAt": "2015-10-23T09:05:43",
     *             "editedAt": "2015-10-23T09:05:43"
     *         },
     *         {
     *             "type": "image",
     *             "uuid": "7ee58145-407f-4021-91fb-415e60302b2a",
     *             "title": "Brains!",
     *             "description": null,
     *             "payloadUrl": "\/\/media.example.org\/v\/ad\/ac\/kb\/1751909b7fe87062ead7af921c4aac24.jpg",
     *             "payload": null,
     *             "previewImage": "\/\/media.example.org\/v\/ad\/ac\/kb\/1751909b7fe87062ead7af921c4aac24.jpg",
     *             "createdAt": "2015-10-23T09:06:11",
     *             "editedAt": "2015-10-23T09:06:36"
     *         },
     *         {
     *             "payloadUrl": "\/\/media.example.org\/v\/ad\/ac\/kb\/86cded2df490b8b0bca4beb500b30611.png",
     *             "type": "file",
     *             "uuid": "0ef12869-c826-491a-a73d-f992b09ce2a9",
     *             "title": "Download",
     *             "description": null,
     *             "payload": null,
     *             "previewImage": "\/\/media.example.org\/v\/ad\/ac\/kb\/86cded2df490b8b0bca4beb500b30611.png",
     *             "createdAt": "2015-10-23T09:07:03",
     *             "editedAt": "2015-10-23T09:07:22"
     *         }
     *     ]
     * }
     * @apiUse NotFoundError
     * 
     * 
     * @param Request $request
     * @param type $uuid
     * @return JSONResponse
     */
    public function listMaterialsAction(Request $request, $uuid) {
        $this->setup($request);
        return $this->reallyList($this->aptRepo->byUuid($uuid));
    }

    /**
     * @api {delete} /api/apt/{aptid}/materials/{id} Remove material.
     * @apiDescription Удаляет материал из списка материалов занятия. 
     * Существующий файл, связанный с материалом не удаляется из библиотеки пользователя.
     * 
     * @apiParam {uuid_string} aptid Appointment ID.
     * @apiParam {uuid_string} id Material ID.
     * 
     * @apiName AptMaterialDelete
     * @apiVersion 2.0.0
     * @apiGroup Appointments
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse EmptyReply
     * @apiUse NotFoundError
     * 
     * 
     * @param Request $request
     * @param type $uuid
     * @return JSONResponse
     */
    public function deleteMaterialAction(Request $request, $aptid, $uuid) {
        $this->setup($request);
        $apt = $this->aptRepo->byUuid($aptid);
        $m = $this->materialRepo->findOneBy(['uuid' => $uuid, 'parent' => $apt]);
        $this->materialM->deleteMaterial($apt, $m, $this->user, true);
        return $this->emptyResponse();
    }

    /**
     * 
     * @param Request $request
     * @param type $aptid
     * @return type
     */
    public function editMaterialsAction(Request $request, $aptid) {
        $this->setup($request);
        $materialsData = $this->getRequestData($request, self::SECTION_ARRAY_NAME);
        $aptO = $this->aptRepo->byUuid($aptid);
        $updated = $this->materialM->updateMaterialsList($materialsData, $aptO, $this->user, true);
        return $this->reallyList($aptO);
    }

    protected function reallyList(Appointment $aptO) {
        if ($this->canEdit($aptO, $this->user, FALSE) or $aptO->isUserEnrolled($this->user)) {
            $list = $this->materialM->listMaterials($aptO, $this->user);
            return new JSONResponse([self::SECTION_ARRAY_NAME => $list]);
        }
        throw new NotFoundHttpException();
    }

    public function getUserFileAction(Request $request, $uuid) {
        $this->setup($request);
        $file = $this->getUserFilesRepository()->byUuid($uuid);
        $info = $this->getUserFileManager()->getUserFileInfo($file, true);
        return new JSONResponse($info);
    }

    /**
     *
     * @var CoreBundle\Repository\RepositoryBase
     */
    protected $repoObj;
    protected $repoMaterial;
    protected $dataSectionName;

    protected function newSetup(Request $r, $section, $getUser = TRUE) {
        $this->user = ($getUser) ? $this->getCurrentUser() : null;
        $this->materialM = $this->container->get('rkk_material_manager');
        $this->repoObj = $this->getManager()->getRepository(MCC::modeltype2Class($section, true));
        $this->repoMaterial = $this->getManager()->getRepository(MCC::materialClassType($section));
        $this->dataSectionName = MCC::materialDataSectionName($section);
    }

    /**
     * @api {post} /api/_t/quizQuestion/{id}/materials Add Material To Quiz Question
     * 
     * @apiParam {uuid_string} id Question ID.
     * 
     * @apiName QuizQMaterialAdd
     * @apiVersion 2.0.0
     * @apiGroup QuizCreation
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse OneMaterialRequest
     * @apiParamExample {json} Request-body:
     * {
     *     "material": {
     *         "type": "image",
     *         "title": "Untitled material",
     *         "description": null,
     *         "payload": {
     *             "uuid": "AmhVRGNULXYx"
     *         },
     *         "previewImage": null,
     *         "uuid": null
     *     }
     * }
     * @apiUse oneMaterialResponse
     * @apiUse NotFoundError
     */

    /**
     * @api {post} /api/_t/quizAnswer/{id}/materials Add Material To Quiz Answer
     * 
     * @apiParam {uuid_string} id Question ID.
     * 
     * @apiName QuizAMaterialAdd
     * @apiVersion 2.0.0
     * @apiGroup QuizCreation
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse OneMaterialRequest
     * @apiParamExample {json} Request-body:
     * {
     *     "material": {
     *         "type": "image",
     *         "title": "Untitled material",
     *         "description": null,
     *         "payload": {
     *             "uuid": "AmhVRGNULXYx"
     *         },
     *         "previewImage": null,
     *         "uuid": null
     *     }
     * }
     * @apiUse oneMaterialResponse
     * @apiUse NotFoundError
     */
    /*
     * @param Request $request
     * @param string $_parentSection
     * @param string $uuid
     * @return JSONResponse
     */
    public function newAddMaterialAction(Request $request, $_parentSection, $uuid) {
        $this->newSetup($request, $_parentSection);

        $obj = $this->repoObj->byUuid($uuid);
        $data = $this->getRequestData($request, $this->dataSectionName);
        $material = $this->materialM->newMaterialFromData($data, $obj, $this->user, TRUE, TRUE, new D2MaterialConverter($_parentSection));
        return $this->oneMaterialResponse($material, $this->dataSectionName);
    }

    /**
     * @api {delete} /api/_t/quizQuestion/{qid}/materials/{id} Remove material from Quiz Question
     * @apiDescription Удаляет материал из списка материалов вопроса. 
     * Существующий файл, связанный с материалом не удаляется из библиотеки пользователя.
     * 
     * @apiParam {uuid_string} qid Question ID.
     * @apiParam {uuid_string} id Material ID.
     * 
     * @apiName QuizQMaterialDelete
     * @apiVersion 2.0.0
     * @apiGroup QuizCreation
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse EmptyReply
     * @apiUse NotFoundError
     */
    /**
     * @api {delete} /api/_t/quizAnswer/{qid}/materials/{id} Remove material from Quiz Answer.
     * @apiDescription Удаляет материал из списка материалов вопроса. 
     * Существующий файл, связанный с материалом не удаляется из библиотеки пользователя.
     * 
     * @apiParam {uuid_string} qid Answer ID.
     * @apiParam {uuid_string} id Material ID.
     * 
     * @apiName QuizAMaterialDelete
     * @apiVersion 2.0.0
     * @apiGroup QuizCreation
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse EmptyReply
     * @apiUse NotFoundError
     */

    /**
     * @param Request $request
     * @param string $_parentSection
     * @param string $oid
     * @param string $uuid
     * @return JSONResponse
     */
    public function newDeleteMaterialAction(Request $request, $_parentSection, $oid, $uuid) {
        $this->newSetup($request, $_parentSection);
        $obj = $this->repoObj->byUuid($oid);
        $m = $this->repoMaterial->findOneBy(['uuid' => $uuid, 'parent' => $obj]);
        $this->materialM->deleteMaterial($obj, $m, $this->user, true);
        return $this->emptyResponse();
    }

    /**
     * @api {put} /api/_t/quizQuestion/{qid}/materials/{id} Edit Material in Quiz Question
     * @apiDescription  Read-write fields: 'title', 'description', 'payload', 'previewimage' - for type=video
     * 
     * Read-only fields: 'uuid', 'createdAt', 'editedAt', 'payloadUrl'
     * 
     * Create-only field: 'type'
     * @apiParam {uuid_string} qid Question ID.
     * @apiParam {uuid_string} id Material ID.
     * 
     * @apiName QuizQMaterialEdit
     * @apiVersion 2.0.0
     * @apiGroup QuizCreation
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse OneMaterialRequest
     * @apiParamExample {json} Request-body:
     * {
     *     "material": {
     *         "type": "image",
     *         "title": "Brains!",
     *         "description": "What do we want?",
     *         "payload": null,
     *         "previewImage": "//media.example.org/v/ad/ac/kb/1751909b7fe87062ead7af921c4aac24.jpg",
     *         "uuid": "7ee58145-407f-4021-91fb-415e60302b2a"
     *     }
     * }
     * @apiUse oneMaterialResponse
     * @apiUse NotFoundError
     */
    /**
     * @api {put} /api/_t/quizAnswer/{qid}/materials/{id} Edit Material in Quiz Answer
     * @apiDescription  Read-write fields: 'title', 'description', 'payload', 'previewimage' - for type=video
     * 
     * Read-only fields: 'uuid', 'createdAt', 'editedAt', 'payloadUrl'
     * 
     * Create-only field: 'type'
     * @apiParam {uuid_string} qid Answer ID.
     * @apiParam {uuid_string} id Material ID.
     * 
     * @apiName QuizAMaterialEdit
     * @apiVersion 2.0.0
     * @apiGroup QuizCreation
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse OneMaterialRequest
     * @apiParamExample {json} Request-body:
     * {
     *     "material": {
     *         "type": "image",
     *         "title": "Brains!",
     *         "description": "What do we want?",
     *         "payload": null,
     *         "previewImage": "//media.example.org/v/ad/ac/kb/1751909b7fe87062ead7af921c4aac24.jpg",
     *         "uuid": "7ee58145-407f-4021-91fb-415e60302b2a"
     *     }
     * }
     * @apiUse oneMaterialResponse
     * @apiUse NotFoundError
     */

    /** @param Request $request
     * @param string $_parentSection
     * @param string $oid
     * @param type $uuid
     * @return JSONResponse
     */
    public function newEditMaterialAction(Request $request, $_parentSection, $oid, $uuid) {
        $this->newSetup($request, $_parentSection);
        $obj = $this->repoObj->byUuid($oid);
        $data = $this->getRequestData($request, $this->dataSectionName);
        $material = $this->repoMaterial->byUuid($uuid);
        $edited = $this->materialM->editMaterial($data, $obj, $material, $this->user, true);
        return $this->oneMaterialResponse($edited, $this->dataSectionName);
    }

    //---- Handitin submissions
    protected $hiiRepo;

    protected function setupHii($getUser = true) {
        $this->hiiRepo = $this->getManager()->getRepository('CoreBundle:AptMaterialHii');
        $this->user = ($getUser) ? $this->getCurrentUser() : null;
        $this->materialM = $this->container->get('rkk_material_manager');
        $this->materialRepo = $this->getManager()->getRepository('CoreBundle:MaterialHii');
    }

    /**
     * @api {post} /api/apt/materials/{hid}/submissions Add Homework Result
     * 
     * @apiParam {uuid_string} hid Homework Id.
     * 
     * @apiName AptSubmissionAdd
     * @apiVersion 2.0.0
     * @apiGroup Homework
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse OneSubmissionRequest
     * @apiParamExample {json} Request-body:
     * {
     *     "submission": {
     *         "type": "image",
     *         "title": "Untitled material",
     *         "description": null,
     *         "payload": {
     *             "uuid": "AmhVRGNULXYx"
     *         },
     *         "final": false, 
     *         "uuid": null
     *     }
     * }
     * @apiUse oneSubmissionResponse
     * @apiUse NotFoundError
     * 
     * @param Request $request
     * @param type $uuid
     * @return JSONResponse
     */
    public function addHiiSubmissionAction(Request $request, $uuid) {
        $this->setupHii($request);
        $hii = $this->hiiRepo->byUuid($uuid);
        $data = $this->getRequestData($request, self::HII_SECTION_NAME);
        $material = $this->materialM->newMaterialFromData($data, $hii, $this->user, TRUE, TRUE, new D2MaterialConverter(MCC::TYPE_AptMaterialHii));
        return $this->oneMaterialResponse($material, self::HII_SECTION_NAME);
    }

    /**
     * @api {delete} /api/apt/materials/{hid}/submissions/{id} Remove homework result
     * @apiDescription
     * Существующий файл, связанный с материалом не удаляется из библиотеки пользователя.
     * 
     * @apiParam {uuid_string} hid Homework ID.
     * @apiParam {uuid_string} id Submission ID.
     * 
     * @apiName AptSubmissionDelete
     * @apiVersion 2.0.0
     * @apiGroup Homework
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse EmptyReply
     * @apiUse NotFoundError
     * 
     * 
     * @param Request $request
     * @param type $uuid
     * @return JSONResponse
     */
    public function deleteHiiSubmissionAction(Request $request, $hid, $uuid) {
        $this->setupHii(true);
        $hii = $this->hiiRepo->byUuid($hid);
        $m = $this->materialRepo->findOneBy(['uuid' => $uuid, 'parent' => $hii]);
        $this->materialM->deleteMaterial($hii, $m, $this->user, true);
        return $this->emptyResponse();
    }

    /**
     * @api {put} /api/apt/materials/{hid}/submissions/{id} Edit Homework Result
     * @apiDescription  Read-write fields: 'title', 'description', 'payload', 'final', 'previewimage' - for type=video
     * 
     * Read-only fields: 'uuid', 'createdAt', 'editedAt', 'payloadUrl'
     * 
     * Create-only field: 'type'
     * @apiParam {uuid_string} hid Homework ID.
     * @apiParam {uuid_string} id Material ID.
     * 
     * @apiName AptSubmissionEdit
     * @apiVersion 2.0.0
     * @apiGroup Homework
     * @apiPermission AuthUser
     * @apiUse Unauthorized
     * 
     * @apiUse OneSubmissionRequest
     * @apiParamExample {json} Request-body:
     * {
     *     "submission": {
     *         "type": "image",
     *         "title": "Brains!",
     *         "description": "What do we want?",
     *         "payload": null,
     *         "previewImage": "//media.example.org/v/ad/ac/kb/1751909b7fe87062ead7af921c4aac24.jpg",
     *         "uuid": "7ee58145-407f-4021-91fb-415e60302b2a",
     *         "final": true
     *     }
     * }
     * @apiUse oneSubmissionResponse
     * @apiUse NotFoundError
     */
    /* @param Request $request
     * @param string $hid
     * @param string $uuid
     * @return JSONResponse
     */
    public function editHiiSubmissionAction(Request $request, $hid, $uuid) {
        $this->setupHii(TRUE);
        $hii = $this->hiiRepo->byUuid($hid);
        $data = $this->getRequestData($request, self::HII_SECTION_NAME);
        $material = $this->materialRepo->byUuid($uuid);
        $edited = $this->materialM->editMaterial($data, $hii, $material, $this->user, true);
        return $this->oneMaterialResponse($edited, self::HII_SECTION_NAME);
    }

    public function listEntitiesAction(Request $r, $pid, $_parentType, $_eType, $_parentField = 'parent') {
        $repoParent = $this->getManager()->getRepository(MCC::modeltype2Class($_parentType));
        $repoObj = $this->getManager()->getRepository(MCC::modeltype2Class($_eType));
        $parent = $repoParent->byUuid($pid);
        $this->canEdit($parent, $this->getCurrentUser());
        $entities = $repoObj->findBy([$_parentField => $parent]);
        $res = (isset($entities)) ? $this->formatReplyList($entities, null, MCC::pluralName($_eType)) : [];
        return new JSONResponse($res);
    }

    public function addEntityAction(Request $r, $pid, $_parentType, $_eType, $_parentField = 'parent', $_r_params = []) {
        $repoParent = $this->getManager()->getRepository(MCC::modeltype2Class($_parentType));
        $parent = $repoParent->byUuid($pid);
        $this->canEdit($parent, $this->getCurrentUser());
        $data = $this->getRequestData($r, $_eType);
        $this->debug(__METHOD__ . ' data ' . var_export($data, true));
        $cargs = [$_parentField => $parent];
        if (0 < count($_r_params)) {
            $cargs += $this->entityCreateHelper($data, $_r_params);
            $this->debug(__METHOD__ . ' cargs ' . var_export(array_keys($cargs), true));
        }
        $e = $this->createEntity($cargs, MCC::modeltype2Class($_eType));
        return $this->entityUpdate($e, $data);
    }

    /**
     * 
     * @param array $data
     * @param array $t_args
     * @return array
     */
    protected function entityCreateHelper(array &$data, array $t_args = []) {
        $ret = [];
        foreach ($t_args as $fieldName => $rule) {
            if (is_array($rule) and isset($data[$fieldName]) and isset($rule['entityType'])) {
                $fieldValue = $data[$fieldName];
                $fieldType = $rule['entityType'];
                $newName = (isset($rule['newName'])) ? $rule['newName'] : $fieldName;
                $val = $this->getManager()
                        ->getRepository(MCC::modeltype2Class($fieldType))
                        ->byUuid($fieldValue);
                $ret[$newName] = $val;
                unset($data[$fieldName]);
                $data[$newName] = $val;
            }
        }
        return $ret;
    }

    protected function createEntity(array &$possibleConstructorData, $class) {
        $reflectionClass = new \ReflectionClass($class);
        $constructor = $reflectionClass->getConstructor();
        if ($constructor) {
            $constructorParameters = $constructor->getParameters();

            $params = array();
            foreach ($constructorParameters as $constructorParameter) {
                $paramName = $constructorParameter->name;
                $key = $paramName;

                if (array_key_exists($key, $possibleConstructorData)) {
                    $params[] = $possibleConstructorData[$key];
                    // don't run set for a parameter passed to the constructor
                    unset($possibleConstructorData[$key]);
                } elseif ($constructorParameter->isDefaultValueAvailable()) {
                    $params[] = $constructorParameter->getDefaultValue();
                } else {
                    $e = sprintf(
                            'Cannot create an instance of %s from serialized data because its '
                            . 'constructor requires parameter "%s" to be present.', $class, $constructorParameter->name
                    );
                    $this->getLogger()->error($e);
                    throw new ApiBadDataException();
                }
            }
            $this->debug(__METHOD__ . ' ' . var_export(array_keys($params), true));
            $obj = $reflectionClass->newInstanceArgs($params);
        } else {
            $obj = new $class();
        }
        $this->debug(__METHOD__ . ' ' . get_class($obj) . ' ' . ($obj instanceof AuthoredEntityInterface));
        if ($obj instanceof AuthoredEntityInterface) {
            $obj->setAuthor($this->getCurrentUser());
        }
        $this->getManager()->persist($obj);
        return $obj;
    }

    public function editEntityAction(Request $r, $pid, $_parentType, $eid, $_eType) {
        $repoParent = $this->getManager()->getRepository(MCC::modeltype2Class($_parentType));
        $parent = $repoParent->byUuid($pid);
        $this->canEdit($parent, $this->getCurrentUser());
        return $this->childEntityUpdate($r, $parent, $_eType, $eid);
    }

    public function deleteEntityAction(Request $r, $pid, $_parentType, $eid, $_eType) {
        $repoObj = $this->getManager()->getRepository(MCC::modeltype2Class($_eType));
        $repoParent = $this->getManager()->getRepository(MCC::modeltype2Class($_parentType));
        $parent = $repoParent->byUuid($pid);
        $this->canEdit($parent, $this->getCurrentUser());
        $obj = $repoObj->byUuid($eid);
        $pMethodName = 'delete' . ucfirst($_eType);
        if (method_exists($parent, $pMethodName)) {
            $parent->$pMethodName($obj->getFeId());
        }
        $repoObj->deleteEntity($obj, true);

        return $this->emptyResponse();
    }

    public function getEntityAction(Request $r, $pid, $_parentType, $eid, $_eType) {
        $repoObj = $this->getManager()->getRepository(MCC::modeltype2Class($_eType));
        $obj = $repoObj->byUuid($eid);
        $this->canEdit($obj, $this->getCurrentUser());
        return new JSONResponse($this->formatReply($obj, $obj->allowedReadList()));
    }

    private $sfDomain = null;

    public function storefrontUrl($object) {
        if (null === $this->sfDomain) {
            $this->sfDomain = $this->getParameter('storefront.domain');
        }
        $sfUrl = sprintf('https://%s.%s/desktop/%s%s', $object->getPrefix(), $this->sfDomain, GlobalConstants::CLIENT_URL_SEPARATOR, GlobalConstants::MARKET_PATH);
        $this->logger->debug(__METHOD__ . $sfUrl);
        return $sfUrl;
    }

    public function storefrontCourseList($object) {
        $this->debug(__METHOD__ . $this->dexport($object));
        if ($sf = $object->getParent() and 0 < count($courses = $sf->getCourses())) {
            $search = [];
            foreach ($courses as $c) {
                $search[] = $this->getCourseRepository()->normalizeId($c);
            }
            
            return $this->getCourseRepository()->getMarketList(
                    new ParameterBag(), $search
            );
        }
        return [];
    }

    protected function formatReply($entity, $attrlist, $typeCallbacks = []) {
        return parent::formatReply($entity, $attrlist, $this->typeCBs());
    }

    protected function formatReplyList($entities, $attrlist, $ashash = false, $typeCallbacks = []) {
        return parent::formatReplyList($entities, $attrlist, $ashash, $this->typeCBs());
    }

    protected function typeCBs() {
        return [
            'CoreBundle\Entity\StorefrontUrl' => [$this, 'storefrontUrl'],
            'CoreBundle\Entity\Placeholders\StorefrontCourseList' => [$this, 'storefrontCourseList']
        ];
    }

    public function approveCertAction($id) {
        if (null === $cert = $this->repoFinalCert()->findOneBy(['internalId' => $id])
                or ( null === $material = $cert->getMaterial())
                or ! $this->canEdit($material, $user = $this->getCurrentUser())
        ) {
            throw new NotFoundHttpException();
        }
        if (!$cert->getApproved()) {
            $mgr = $this->mgrFinalCert();
            $params = $mgr->customTemplateParams($material, $cert->getUser(), $cert);
            $cert->approve()->setUserData($params);
            $mgr->logCertAction($cert, $material, NotificationEventType::CERT_APPROVED, ['instructor' => $user]);
            $this->getManager()->flush();
        }
        $cert = $this->formatReply($cert, $cert->allowedReadList());
        return new JSONResponse($cert);
    }

    /**
     * 
     * @return CoreBundle\Model\FinalCertManager
     */
    protected function mgrFinalCert() {
        return $this->container->get('rkk_finalcert_manager');
    }

}
