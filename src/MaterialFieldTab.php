<?php

namespace samsoncms\app\material;

use samson\activerecord\dbQuery;
use samsoncms\api\MaterialField;

/** 
 * @author Egorov Vitaly <egorov@samsonos.com>
 */
class MaterialFieldTab extends FormTab
{
    /** Meta static variable to disable default form rendering */
    public static $AUTO_RENDER = false;

    /** Tab sorting index for header sorting */
    public $index = 1;

    /** Tab content view path */
    private $content_view = 'form/tab/content/materialfield';

    /**
     * CMS Field object
     * @var \samsoncms\input\wysiwyg\Application
     */
    protected $cmsfield;

    /**
     * MaterialField DB object pointer
     * @var MaterialField
     * @see \samsoncms\api\MaterialField
     */
    protected $db_mf;

    /**
     * Constructor
     * @param Form $form
     * @param FormTab $parent
     * @param MaterialField $db_mf
     * @param string $locale
     * @param string $field_type
     */
    public function __construct(Form & $form, FormTab & $parent, MaterialField & $db_mf, $locale = null, $field_type = 'WYSIWYG')
    {
        // Create CMS Field object from MaterialField object
        $this->cmsfield = m('samsoncms_input_wysiwyg_application')->createField(new dbQuery(), $db_mf);

        // Save tab header name as locale name
        $this->name = $locale;

        // Generate unique html identifier
        $this->id = utf8_translit($parent->name) . '_' . $this->name . '_tab';

        // Save pointers to database MaterialField object
        $this->db_mf = &$db_mf;

        // Call parent
        parent::__construct($form, $parent);

        //elapsed($this->content_view.'!!!!!!!!!!!!!!');

        // Render CMS Field
        $this->content_html = m('material')->view($this->content_view)
            ->cmsfield($this->cmsfield)
            ->output();
    }
}