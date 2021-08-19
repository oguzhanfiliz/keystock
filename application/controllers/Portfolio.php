<?php

class Portfolio extends CI_Controller
{
    public $viewFolder = "";

    public function __construct()
    {

        parent::__construct();

        $this->viewFolder = "portfolio_v";

        $this->load->model("portfolio_model");
        $this->load->model("portfolio_image_model");
        $this->load->model("portfolio_category_model");

        if(!get_active_user()){
            redirect(base_url("login"));
        }

    }

    public function index(){

        $viewData = new stdClass();

        /** Tablodan Verilerin Getirilmesi.. */
        $items = $this->portfolio_model->get_all(
            array(), "rank ASC"
        );

        /** View'e gönderilecek Değişkenlerin Set Edilmesi.. */
        $viewData->viewFolder = $this->viewFolder;
        $viewData->subViewFolder = "list";
        $viewData->items = $items;

        $this->load->view("{$viewData->viewFolder}/{$viewData->subViewFolder}/index", $viewData);
    }

    public function new_form(){

        $viewData = new stdClass();

        $viewData->categories = $this->portfolio_category_model->get_all(
            array(
                "isActive"  => 1
            )
        );

        /** View'e gönderilecek Değişkenlerin Set Edilmesi.. */
        $viewData->viewFolder = $this->viewFolder;
        $viewData->subViewFolder = "add";

        $this->load->view("{$viewData->viewFolder}/{$viewData->subViewFolder}/index", $viewData);

    }

    public function save(){

        $this->load->library("form_validation");

        // Validation Kurallar yazilir..
        $this->form_validation->set_rules("name", "Ürün Adı", "required|trim");
        $this->form_validation->set_rules("category_id", "Kategori", "required|trim");
        $this->form_validation->set_rules("price", "Fiyat", "required|trim");
        $this->form_validation->set_rules("count", "Adet", "required|trim");

        $this->form_validation->set_message(
            array(
                "required"  => "<b>{field}</b> alanı boş geçilemez"
            )
        );

        $validate = $this->form_validation->run();

        if($validate){
            try{
                $category_id = $this->input->post("category_id");
            }catch(Exception $e){
                $alert = array(
                    "title" => "İşlem Başarısız",
                    "text" => "Kayıt Ekleme sırasında bir problem oluştu" . $e->getMessage(),
                    "type"  => "error"
                );
            }

            $record =    array(
                "name"          => $this->input->post("name"),
                "description"   => $this->input->post("description"),
                "price"         => $this->input->post("price"),
                "count"        => $this->input->post("count"),
                "category_id"   => $category_id,
                "rank"          => 0,
                "isActive"      => 1,
                "createdAt"     => date("Y-m-d H:i:s")
            );


            $beforeRecord = $this->portfolio_category_model->get( 
                array(
                "id"    => $category_id,
                
                )
            );
            $triggerUpdateData = createTriggerEvent($beforeRecord,$record);
            try
            {
                
            $this->db->trans_start(FALSE);
            $insert = $this->portfolio_model->add($record);            
            $update = $this->portfolio_category_model->update(
                array(
                        "id" => $this->input->post("category_id")
                ),
                array(
                    "count" => $triggerUpdateData[0],
                    "total" => $triggerUpdateData[1]
                )
            );
            $this->db->trans_complete();
            }catch(Exception $e)
            {
                $alert = array(
                    "title" => "İşlem Başarısız",
                    "text" => "Kayıt Ekleme sırasında bir problem oluştu" . $e->getMessage(),
                    "type"  => "error"
                );
            }

            // TODO Alert sistemi eklenecek...
            if($insert){

                $alert = array(
                    "title" => "İşlem Başarılı",
                    "text" => "Kayıt başarılı bir şekilde eklendi",
                    "type"  => "success"
                );

            } else {

                $alert = array(
                    "title" => "İşlem Başarısız",
                    "text" => "Kayıt Ekleme sırasında bir problem oluştu",
                    "type"  => "error"
                );
            }

            // İşlemin Sonucunu Session'a yazma işlemi...
            $this->session->set_flashdata("alert", $alert);

            redirect(base_url("portfolio"));

        } else {

            $viewData = new stdClass();

            /** View'e gönderilecek Değişkenlerin Set Edilmesi.. */
            $viewData->viewFolder = $this->viewFolder;
            $viewData->subViewFolder = "add";
            $viewData->form_error = true;
            $viewData->categories = $this->portfolio_category_model->get_all(
                array(
                    "isActive"  => 1
                )
            );
            $this->load->view("{$viewData->viewFolder}/{$viewData->subViewFolder}/index", $viewData);
        }

        // Başarılı ise
            // Kayit işlemi baslar
        // Başarısız ise
            // Hata ekranda gösterilir...

    }

    public function update_form($id){

        $viewData = new stdClass();

        /** Tablodan Verilerin Getirilmesi.. */
        $item = $this->portfolio_model->get(
            array(
                "id"    => $id,
            )
        );
        $viewData->categories = $this->portfolio_category_model->get_all(
            array(
                "isActive"  => 1
            )
        );
        
        /** View'e gönderilecek Değişkenlerin Set Edilmesi.. */
        $viewData->viewFolder = $this->viewFolder;
        $viewData->subViewFolder = "update";
        $viewData->item = $item;

        $this->load->view("{$viewData->viewFolder}/{$viewData->subViewFolder}/index", $viewData);


    }

    public function update($id){

        $this->load->library("form_validation");

        // Kurallar yazilir..
        $this->form_validation->set_rules("name", "Ürün Adı", "required|trim");
        $this->form_validation->set_rules("category_id", "Kategori", "required|trim");
        $this->form_validation->set_rules("price", "Fiyat", "required|trim");
        $this->form_validation->set_rules("count", "Adet", "required|trim");


        $this->form_validation->set_message(
            array(
                "required"  => "<b>{field}</b> alanı doldurulmalıdır"
            )
        );

        $validate = $this->form_validation->run();

        if($validate){
            try{
                $category_id = $this->input->post('category_id');
            }catch(Exception $e){
                
                $alert = array(
                    "title" => "İşlem Başarısız",
                    "text" => "Kayıt Ekleme sırasında bir problem oluştu" . $e->getMessage(),
                    "type"  => "error"
                );
            }
            
            $request =    array(
                "name"         => $this->input->post("name"),
                "description"  => $this->input->post("description"),
                "price"        => $this->input->post("price"),
                "count"        => $this->input->post("count"),
                "category_id"  => $this->input->post("category_id"),
            
            );
            $recordProduct = $this->portfolio_model->get( array("id" => $id));
            $record  = $this->portfolio_category_model->get(array("id" => $category_id));
            try{
                $this->db->trans_start();
                $triggerUpdateData = updateTriggerEvent($record,$request,$recordProduct);
                $update = $this->portfolio_model->update(
                    array(
                        "id"    => $id
                    ),$request);
                $update_category = $this->portfolio_category_model->update(
                        array(
                                "id" => $category_id
                        ),
                        array(
                            "count" => $triggerUpdateData[0],
                            "total" => $triggerUpdateData[1]
                        )
                    );
                $this->db->trans_complete();

            }catch(Exception $e){
                $alert = array(
                    "title" => "İşlem Başarısız",
                    "text" => "Güncelleme sırasında bir problem oluştu".$e->getMessage(),
                    "type"  => "error"
                );

            }
            if($update && $update_category){

                $alert = array(
                    "title" => "İşlem Başarılı",
                    "text" => "Kayıt başarılı bir şekilde güncellendi",
                    "type"  => "success"
                );

            } else {

                $alert = array(
                    "title" => "İşlem Başarısız",
                    "text" => "Güncelleme sırasında bir problem oluştu",
                    "type"  => "error"
                );


            }

            $this->session->set_flashdata("alert", $alert);
            redirect(base_url("portfolio"));

        } else {

            $viewData = new stdClass();

            /** Tablodan Verilerin Getirilmesi.. */
            $item = $this->portfolio_model->get(
                array(
                    "id"    => $id,
                )
            );

            /** View'e gönderilecek Değişkenlerin Set Edilmesi.. */
            $viewData->viewFolder = $this->viewFolder;
            $viewData->subViewFolder = "update";
            $viewData->form_error = true;
            $viewData->item = $item;
            $viewData->categories = $this->portfolio_category_model->get_all(
                array(
                    "isActive"  => 1
                )
            );

            $this->load->view("{$viewData->viewFolder}/{$viewData->subViewFolder}/index", $viewData);
        }

        // Başarılı ise
        // Kayit işlemi baslar
        // Başarısız ise
        // Hata ekranda gösterilir...

    }

    public function delete($id){

        $request = $this->portfolio_model->get(
                array(
                    "id" => $id
                )
            );
        $category_id = $request->category_id;
        $record = $this->portfolio_category_model->get(
                array(
                    "id" => $category_id
                )
            );
        

        try{


            $this->db->trans_start();

            $delete = $this->portfolio_model->delete(
                array(
                    "id"    => $id
                )
            );
            $triggerUpdateData = deleteTriggerEvent($record,$request);

            if($triggerUpdateData == 0){$delete = false;}
            $deleteStok = $this->portfolio_category_model->update(   
                array(
                    "id" => $category_id
                ),
                array(
                    "count" => $triggerUpdateData['count'],
                    "total" => $triggerUpdateData['total']
                ));
            
            $this->db->trans_complete();
            
        }catch(Exception $e){
            $alert = array(
                "title" => "İşlem Başarısız",
                "text" => "Kayıt silme sırasında bir problem oluştu ".$e->getMessage(),
                "type"  => "error"
            );
        }


        if($delete){

            $alert = array(
                "title" => "İşlem Başarılı",
                "text" => "Kayıt başarılı bir şekilde silindi",
                "type"  => "success"
            );

        } else {

            $alert = array(
                "title" => "İşlem Başarısız",
                "text" => "Kayıt silme sırasında bir problem oluştu",
                "type"  => "error"
            );


        }

        $this->session->set_flashdata("alert", $alert);
        redirect(base_url("portfolio"));


    }

    public function imageDelete($id, $parent_id){

        $fileName = $this->portfolio_image_model->get(
            array(
                "id"    => $id
            )
        );

        $delete = $this->portfolio_image_model->delete(
            array(
                "id"    => $id
            )
        );


        // TODO Alert Sistemi Eklenecek...
        if($delete){

            unlink("uploads/{$this->viewFolder}/$fileName->img_url");

            redirect(base_url("portfolio/image_form/$parent_id"));
        } else {
            redirect(base_url("portfolio/image_form/$parent_id"));
        }

    }

    public function isActiveSetter($id){

        if($id){

            $isActive = ($this->input->post("data") === "true") ? 1 : 0;

            $this->portfolio_model->update(
                array(
                    "id"    => $id
                ),
                array(
                    "isActive"  => $isActive
                )
            );
        }
    }

    public function imageIsActiveSetter($id){

        if($id){

            $isActive = ($this->input->post("data") === "true") ? 1 : 0;

            $this->portfolio_image_model->update(
                array(
                    "id"    => $id
                ),
                array(
                    "isActive"  => $isActive
                )
            );
        }
    }
    //iscoversetter işlemleri yapıldı
    public function isCoverSetter($id, $parent_id){

        if($id && $parent_id){

            $isCover = ($this->input->post("data") === "true") ? 1 : 0;

            // Kapak yapılmak istenen kayıt
            $this->portfolio_image_model->update(
                array(
                    "id"         => $id,
                    "portfolio_id" => $parent_id
                ),
                array(
                    "isCover"  => $isCover
                )
            );


            // Kapak yapılmayan diğer kayıtlar
            $this->portfolio_image_model->update(
                array(
                    "id !="      => $id,
                    "portfolio_id" => $parent_id
                ),
                array(
                    "isCover"  => 0
                )
            );

            $viewData = new stdClass();

            /** View'e gönderilecek Değişkenlerin Set Edilmesi.. */
            $viewData->viewFolder = $this->viewFolder;
            $viewData->subViewFolder = "image";

            $viewData->item_images = $this->portfolio_image_model->get_all(
                array(
                    "portfolio_id"    => $parent_id
                ), "rank ASC"
            );

            $render_html = $this->load->view("{$viewData->viewFolder}/{$viewData->subViewFolder}/render_elements/image_list_v", $viewData, true);

            echo $render_html;

        }
    }

    public function rankSetter(){


        $data = $this->input->post("data");

        parse_str($data, $order);

        $items = $order["ord"];

        foreach ($items as $rank => $id){

            $this->portfolio_model->update(
                array(
                    "id"        => $id,
                    "rank !="   => $rank
                ),
                array(
                    "rank"      => $rank
                )
            );

        }

    }

    public function imageRankSetter(){


        $data = $this->input->post("data");

        parse_str($data, $order);

        $items = $order["ord"];

        foreach ($items as $rank => $id){

            $this->portfolio_image_model->update(
                array(
                    "id"        => $id,
                    "rank !="   => $rank
                ),
                array(
                    "rank"      => $rank
                )
            );

        }

    }

    public function image_form($id){

        $viewData = new stdClass();

        /** View'e gönderilecek Değişkenlerin Set Edilmesi.. */
        $viewData->viewFolder = $this->viewFolder;
        $viewData->subViewFolder = "image";

        $viewData->item = $this->portfolio_model->get(
            array(
                "id"    => $id
            )
        );

        $viewData->item_images = $this->portfolio_image_model->get_all(
            array(
                "portfolio_id"    => $id
            ), "rank ASC"
        );

        $this->load->view("{$viewData->viewFolder}/{$viewData->subViewFolder}/index", $viewData);
    }

    public function image_upload($id){

        $file_name = convertToSEO(pathinfo($_FILES["file"]["name"], PATHINFO_FILENAME)) . "." . pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);

        $config["allowed_types"] = "jpg|jpeg|png";
        $config["upload_path"]   = "uploads/$this->viewFolder/";
        $config["file_name"] = $file_name;

        $this->load->library("upload", $config);

        $upload = $this->upload->do_upload("file");

        if($upload){

            $uploaded_file = $this->upload->data("file_name");

            $this->portfolio_image_model->add(
                array(
                    "img_url"       => $uploaded_file,
                    "rank"          => 0,
                    "isActive"      => 1,
                    "isCover"       => 0,
                    "createdAt"     => date("Y-m-d H:i:s"),
                    "portfolio_id"    => $id
                )
            );


        } else {
            echo "islem basarisiz";
        }

    }

    public function refresh_image_list($id){

        $viewData = new stdClass();

        /** View'e gönderilecek Değişkenlerin Set Edilmesi.. */
        $viewData->viewFolder = $this->viewFolder;
        $viewData->subViewFolder = "image";

        $viewData->item_images = $this->portfolio_image_model->get_all(
            array(
                "portfolio_id"    => $id
            )
        );

        $render_html = $this->load->view("{$viewData->viewFolder}/{$viewData->subViewFolder}/render_elements/image_list_v", $viewData, true);

        echo $render_html;

    }

}
