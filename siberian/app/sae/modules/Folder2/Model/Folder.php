<?php

/**
 * Class Folder2_Model_Folder
 *
 * @method integer getId()
 * @method Folder2_Model_Db_Table_Folder getTable()
 * @method $this setRootCategoryId(integer $categoryId)
 */
class Folder2_Model_Folder extends Core_Model_Default {

    /**
     * @var array
     */
    public $cache_tags = [
        'feature_folder2',
    ];

    /**
     * @var bool
     */
    protected $_is_cacheable = true;

    /**
     * @var
     */
    protected $_root_category;

    /**
     * Folder2_Model_Folder constructor.
     * @param array $params
     */
    public function __construct($params = []) {
        parent::__construct($params);
        $this->_db_table = 'Folder2_Model_Db_Table_Folder';

        // Default to version 2!
        $this->setVersion(2);

        return $this;
    }

    /**
     * @return bool
     */
    public function getShowSearch() {
        return ($this->getData('show_search') === '1');
    }

    /**
     * @param $valueId
     * @return array
     */
    public function getInappStates($valueId) {
        $inAppStates = [
            [
                'state' => 'folder-category-list',
                'offline' => true,
                'params' => [
                    'value_id' => $valueId,
                ],
            ]
        ];

        return $inAppStates;
    }

    /**
     * @param Application_Model_Option_Value $optionValue
     * @return array
     */
    public function getFeaturePaths($optionValue) {
        if (!$this->isCacheable()) {
            return [];
        }

        $valueId = $optionValue->getId();
        $cacheId = "feature_paths_valueid_{$valueId}";
        if (!$result = $this->cache->load($cacheId)) {

            $paths = [];
            $paths[] = $optionValue->getPath('findall', [
                'value_id' => $optionValue->getId()
            ], false);

            $paths = array_merge($paths, $this->_get_subcategories_feature_paths($this->getRootCategory(), $optionValue));

            $this->cache->save($paths, $cacheId,
                $this->cache_tags + [
                    'feature_paths',
                    'feature_paths_valueid_' . $valueId
                ]);
        } else {
            $paths = $result;
        }

        return $paths;
    }

    /**
     * @param Application_Model_Option_Value $optionValue
     * @return array
     */
    public function getAssetsPaths($optionValue) {
        if (!$this->isCacheable()) {
            return [];
        }

        $paths = [];

        $valueId = $optionValue->getId();
        $cacheId = 'assets_paths_valueid_' . $valueId;
        if (!$result = $this->cache->load($cacheId)) {

            $folder = $optionValue->getObject();

            if ($folder->getId()) {
                $category = new Folder2_Model_Category();
                $category->find($folder->getRootCategoryId(), 'category_id');
                if ($category->getId()) {
                    $paths[] = $category->getPictureUrl();
                    $paths = array_merge($paths, $this->_get_subcategories_assets_paths($category));
                }
            }

            $this->cache->save($paths, $cacheId,
                $this->cache_tags + [
                    'assets_paths',
                    'assets_paths_valueid_' . $valueId
                ]);
        } else {
            $paths = $result;
        }

        return $paths;
    }

    /**
     * @param Application_Model_Option_Value $optionValue
     * @return bool|array
     */
    public function getEmbedPayload($optionValue = null) {
        $payload = [
            'sections' => [],
            'page_title' => $optionValue->getTabbarName()
        ];

        if ($this->getId()) {
            $request = $optionValue->getRequest();

            $categoryId = $request->getParam('category_id', null);
            $currentCategory = new Folder2_Model_Category();

            if ($categoryId) {
                $currentCategory->find($categoryId, 'category_id');
            }

            $object = $optionValue->getObject();
            if (!$object->getId() ||
                    ($currentCategory->getId() &&
                    $currentCategory->getRootCategoryId() != $object->getRootCategoryId())) {
                throw new Siberian_Exception(__("An error occurred during process. Please try again later."));
            }

            $colorCode = 'background';
            if ($this->getApplication()->useIonicDesign()) {
                $colorCode = 'list_item';
            }
            $color = $this->getApplication()->getBlock($colorCode)->getImageColor();

            // Here we get the list used for the search in folder feature!
            $currentOption = $optionValue;
            $folder = new Folder2_Model_Folder();
            $category = new Folder2_Model_Category();
            $folder->find($currentOption->getId(), 'value_id');

            $showSearch = $folder->getShowSearch();

            $category->find($folder->getRootCategoryId(), 'category_id') ;

            $result = [];
            array_push($result, $category);
            $this->_getAllChildren($category, $result);

            $searchList = [];

            $optionPictureb64 = null;

            foreach ($result as $folder) {
                $pictureB64 = null;
                if ($currentOption->getIconId()) {
                    $pictureFile = Core_Controller_Default_Abstract::sGetColorizedImage($currentOption->getIconId(), $color);
                    $pictureB64 = $request->getBaseUrl() . $pictureFile;
                    $optionPictureb64 = $request->getBaseUrl() . $pictureFile;
                }

                $url = $this->getPath("folder2/mobile_list", array(
                        "value_id" => (integer) $currentOption->getId(),
                        "category_id" => (integer) $folder->getId())
                );

                $searchList[] = array(
                    "name" => $folder->getTitle(),
                    "father_name" => $folder->getFatherName(),
                    "url" => $url,
                    "path" => $url,
                    "picture" => $pictureB64,
                    "offline_mode" => (boolean) $folder->isCacheable(),
                    "type" => "folder"
                );
                $category_option = new Application_Model_Option_Value();
                $category_options = $category_option->findAll(array(
                    "app_id" => (integer) $this->getApplication()->getId(),
                    "folder_category_id" => (integer) $folder->getCategoryId(),
                    "is_visible" => true,
                    "is_active" => true
                ), array("folder_category_position ASC"));

                foreach($category_options as $feature) {
                    /**
                    START Link special code
                    We get informations about link at homepage level
                     */
                    $hide_navbar = false;
                    $use_external_app = false;
                    if($object_link = $feature->getObject()->getLink() AND is_object($object_link)) {
                        $hide_navbar = $object_link->getHideNavbar();
                        $use_external_app = $object_link->getHideNavbar();
                    }
                    /**
                    END Link special code
                     */

                    $pictureB64 = null;
                    if ($feature->getIconId()) {
                        $pictureFile = Core_Controller_Default_Abstract::sGetColorizedImage($feature->getIconId(), $color);
                        $pictureB64 = $request->getBaseUrl() . $pictureFile;
                    }

                    $url = $feature->getPath(null, array("value_id" => $feature->getId()), false);

                    $searchList[] = array(
                        "name" => $feature->getTabbarName(),
                        "father_name" => $folder->getTitle(),
                        "url" => $url,
                        "path" => $url,
                        "is_link" => !(boolean) $feature->getIsAjax(),
                        "hide_navbar" => (boolean) $hide_navbar,
                        "use_external_app" => (boolean) $use_external_app,
                        "picture" => $pictureB64,
                        "offline_mode" => (boolean) $feature->getObject()->isCacheable(),
                        "code" => $feature->getCode(),
                        "type" => "feature",
                        "is_locked" => (boolean) $feature->isLocked()
                    );
                }
            }

            if (!$currentCategory->getId()) {
                $currentCategory = $object->getRootCategory();
            }

            $payload = [
                'folders' => [],
                'show_search' => (boolean) $showSearch,
                'category_id' => (integer) $categoryId
            ];

            $subcategories = $currentCategory->getChildren();

            foreach($subcategories as $subcategory) {

                $pictureB64 = null;
                if($subcategory->getPictureUrl()) {
                    $pictureB64 = $request->getBaseUrl() . $subcategory->getPictureUrl();
                }

                $url = __path('folder/mobile_list', array(
                    'value_id' => $currentOption->getId(),
                    'category_id' => $subcategory->getId()
                ));

                $payload['folders'][] = [
                    'title' => $subcategory->getTitle(),
                    'subtitle' => $subcategory->getSubtitle(),
                    'picture' => $subcategory->getPictureUrl() ? $pictureB64 : $optionPictureb64,
                    'url' => $url,
                    'path' => $url,
                    'offline_mode' => (boolean) $currentOption->getObject()->isCacheable(),
                    'category_id' => (integer) $subcategory->getId(),
                    'is_subfolder' => true
                ];
            }

            $pages = $currentCategory->getPages();

            foreach($pages as $page) {
                /**
                START Link special code
                We get informations about link at homepage level
                 */
                $hide_navbar = false;
                $use_external_app = false;
                if($object_link = $page->getObject()->getLink() AND is_object($object_link)) {
                    $hide_navbar = $object_link->getHideNavbar();
                    $use_external_app = $object_link->getUseExternalApp();
                }

                $pictureB64 = null;
                if ($page->getIconId()) {
                    $icon = Core_Controller_Default::sGetColorizedImage($page->getIconId(), $color);
                    $pictureB64 = $request->getBaseUrl() . $icon;
                }

                /**
                END Link special code
                 */
                $url = $page->getPath(null, [
                    'value_id' => $page->getId()
                ], false);

                $payload['folders'][] = [
                    'title' => $page->getTabbarName(),
                    'subtitle' => '',
                    'picture' => $pictureB64,
                    'hide_navbar' => (boolean) $hide_navbar,
                    'use_external_app'  => (boolean) $use_external_app,
                    'is_link' => !(boolean) $page->getIsAjax(),
                    'url' => $url,
                    'path' => $url,
                    'code' => $page->getCode(),
                    'offline_mode' => (boolean) $page->getObject()->isCacheable(),
                    'embed_payload' => $page->getEmbedPayload($request),
                    'is_locked' => (boolean) $page->isLocked(),
                    'touched_at' => (integer) $page->getTouchedAt(),
                    'expires_at' => (integer) $page->getExpiresAt(),
                    'has_parent_folder' => true,
                ];
            }

            $coverB64 = null;
            if ($currentCategory->getPictureUrl()) {
                $coverB64 = $request->getBaseUrl() . $currentCategory->getPictureUrl();
            }

            $payload['cover'] = [
                'title' => $currentCategory->getTitle(),
                'subtitle' => $currentCategory->getSubtitle(),
                'picture' => $coverB64
            ];

            $payload['search_list'] = $searchList;
            $payload['page_title'] = $currentCategory->getTitle();
            $payload['success'] = true;

        }

        return $payload;
    }

    private function _getAllChildren($category, &$tab_children) {
        $children = $category->getChildren();
        foreach($children as $child) {
            if($category->getCategoryId() === $category->getParentId()) {
                continue;
            }
            $child->setFatherName($category->getTitle());
            array_push($tab_children, $child);
            $this->_getAllChildren($child, $tab_children);
        }
    }

    public function deleteFeature() {

        if(!$this->getId()) {
            return $this;
        }

        $this->getRootCategory()->delete();

        return $this->delete();
    }

    public function getRootCategory() {

        if(!$this->_root_category) {
            $this->_root_category = new Folder2_Model_Category();
            $this->_root_category->find($this->getRootCategoryId());
        }

        return $this->_root_category;

    }

    private function _get_subcategories_feature_paths($category, $option_value) {
        $paths = array();
        $subcategories = $category->getChildren();

        foreach($subcategories as $subcategory) {
            $params = array(
                "value_id" => $option_value->getId(),
                "category_id" => $subcategory->getId()
            );
            $paths[] = $option_value->getPath("findall", $params, false);
            $paths = array_merge($paths, $this->_get_subcategories_feature_paths($subcategory, $option_value));
        }

        return $paths;
    }

    private function _get_subcategories_assets_paths($category) {
        $paths = array();

        if(is_object($category) && $category->getId()) {
            $subs = $category->getChildren();
            foreach($subs as $subcat) {
                $paths[] = $subcat->getPictureUrl();
                $paths = array_merge($paths, $this->_get_subcategories_assets_paths($subcat));
            }
        }

        return $paths;
    }

}