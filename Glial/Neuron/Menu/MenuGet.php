<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class MenuGet extends Controller
{
    function diplay($group_id, $attr = '')
    {
        global $db;
        $tree = new Tree;
        $sql = sprintf(
                'SELECT * FROM %s WHERE group_id = %s ORDER BY %s, %s', MENU_TABLE, $group_id, MENU_PARENT, MENU_POSITION
        );
        $db = $this->di['db']->sql(DB_DEFAULT);
        $res = $db->sql_query($sql);
        while ($row = $db->sql_fetch_array($res)) {
            $label = '<a href="' . $row[MENU_URL] . '">';
            $label .= $row[MENU_TITLE];
            $label .= '</a>';
            $li_attr = '';
            if ($row[MENU_CLASS]) {
                $li_attr = ' class="' . $row[MENU_CLASS] . '"';
            }
            $tree->add_row($row[MENU_ID], $row[MENU_PARENT], $li_attr, $label);
        }
        $menu = $tree->generate_list($attr);
        return $menu;
    }
    function index()
    {
        $ressources = $this->di['acl']->getResources();
        
        $data['ressource'] = array();
        foreach($ressources as $ressource => $val)
        {
            $tmp = array();
            
            $tmp['id'] = $ressource;
            $tmp['libelle'] = $ressource;
            
            $data['ressource'][] = $tmp;
        }
        
        $this->addJavascript(array("jquery-latest.min.js", "jquery-ui-1.10.3.custom.min.js", "jquery.mjs.nestedSortable.js", "menu.js", "bootstrap.min.js"));
        //$data['menu'] = $this->diplay(1);
        $data['menum'] = $this->menuManager(1);
        $this->set('data', $data);
    }
    public function menuManager($id_group = 1)
    {
        $db = $this->di['db']->sql(DB_DEFAULT);
        $sql = sprintf('SELECT * FROM %s WHERE %s = %s ORDER BY %s, %s', MENU_TABLE, MENU_GROUP, $id_group, MENU_PARENT, MENU_POSITION);
        $menu = $db->sql_fetch_yield($sql);
        $data['menu_ul'] = '<ul id="easymm"></ul>';
        if ($menu) {
            //include _DOC_ROOT . 'includes/tree.php';
            $tree = new Tree;
            foreach ($menu as $row) {
                $tree->add_row(
                        $row[MENU_ID], $row[MENU_PARENT], ' id="menu-' . $row[MENU_ID] . '" class="sortable"', $this->get_label($row)
                );
            }
            $data['menu_ul'] = $tree->generate_list('id="easymm"');
        }
        $data['group_id'] = $id_group;
        $data['group_title'] = $db->sql_fetch_all(sprintf('SELECT %s FROM %s WHERE %s = %s', MENUGROUP_TITLE, MENUGROUP_TABLE, MENUGROUP_ID, $id_group))[0];
        $data['menu_groups'] = $db->sql_fetch_all(sprintf('SELECT %s, %s FROM %s', MENUGROUP_ID, MENUGROUP_TITLE, MENUGROUP_TABLE))[0];
        return $data;
    }
    /**
     * Get label for list item in menu manager
     * this is the content inside each <li>
     *
     * @param array $row
     * @return string
     */
    private function get_label($row)
    {
        $label = '<div class="ns-row">' .
                '<div class="ns-title">' . $row[MENU_TITLE] . '</div>' .
                '<div class="ns-url">' . $row[MENU_URL] . '</div>' .
                '<div class="ns-class">' . $row[MENU_CLASS] . '</div>' .
                '<div class="ns-actions">' .
                '<a href="#" class="" title="Edit">' .
                '<span class="glyphicon glyphicon-cog"></span>' .
                '</a>' .
                '<a href="#" class="delete-menu" title="Delete">' .
                '<span class="glyphicon glyphicon-remove">' .
                '</a>' .
                '<input type="hidden" name="menu_id" value="' . $row[MENU_ID] . '">' .
                '</div>' .
                '</div>';
        return $label;
    }
    /**
     * new save position method
     */
    public function save_position()
    {
        $this->layout_name = false;
        $this->layout = false;
        $this->view = false;
        $this->is_ajax = true;
        $db = $this->di['db']->sql(DB_DEFAULT);
        if (!empty($_POST)) {
            $menu = $_POST['menu'];
            foreach ($menu as $k => $v) {
                if ($v == 'null') {
                    $menu2[0][] = $k;
                } else {
                    $menu2[$v][] = $k;
                }
            }
            if (!empty($menu2)) {
                $sql = '';
                foreach ($menu2 as $k => $v) {
                    $i = 1;
                    foreach ($v as $v2) {
                        $sql .= "UPDATE menu SET parent_id = " . $db->sql_real_escape_string($k) . ", position = " . $db->sql_real_escape_string($i) . " WHERE id = " . $db->sql_real_escape_string($v2) . ";";
                        //$db->sql_query($sql);
                        $i++;
                    }
                }
                $db->sql_multi_query($sql);
                echo "true";
            }
        }
    }
    public function addItem()
    {
        $this->view = false;
        $this->layout_name = false;
        var_dump($_POST);
        if (isset($_POST['title'])) {
            $data[MENU_TITLE] = trim($_POST['title']);
            if (!empty($data[MENU_TITLE])) {
                $data[MENU_URL] = $_POST['url'];
                $data[MENU_CLASS] = $_POST['class'];
                $data[MENU_GROUP] = $_POST['group_id'];
                
                
                
                $data[MENU_POSITION] = $this->get_last_position($_POST['group_id']) + 1;
                var_dump($data);
                if ($this->db->insert(MENU_TABLE, $data)) {
                    $data[MENU_ID] = $this->db->Insert_ID();
                    $response['status'] = 1;
                    $li_id = 'menu-' . $data[MENU_ID];
                    $response['li'] = '<li id="' . $li_id . '" class="sortable">' . $this->get_label($data) . '</li>';
                    $response['li_id'] = $li_id;
                } else {
                    $response['status'] = 2;
                    $response['msg'] = 'Add menu error.';
                }
            } else {
                $response['status'] = 3;
            }
            header('Content-type: application/json');
            echo json_encode($response);
        }
    }
    public function deleteItem()
    {
        if (isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            $this->get_descendants($id);
            if (!empty($this->ids)) {
                $ids = implode(', ', $this->ids);
                $id = "$id, $ids";
            }
            $sql = sprintf('DELETE FROM %s WHERE %s IN (%s)', MENU_TABLE, MENU_ID, $id);
            $delete = $this->db->Execute($sql);
            if ($delete) {
                $response['success'] = true;
            } else {
                $response['success'] = false;
            }
            header('Content-type: application/json');
            echo json_encode($response);
        }
    }
    
    
    
    
}