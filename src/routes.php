<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    
    function getDir($db,$id_user){
        $sql = "SELECT username, nama_lengkap,alamat_lengkap,kode_kota,kode_role,id_user FROM user 
                WHERE id_user =:id_user";
            $stmt = $db->prepare($sql);
            $data = [
                ":id_user" => $id_user
            ];
            $respCode = 200;
            $dir = "";
            if($stmt->execute($data)){
                if ($stmt->rowCount() > 0) {
                    $data = $stmt->fetch();
                    $dir = strtoupper(explode(" ", $data['nama_lengkap'])[0]).preg_replace('/\./', '', $data['kode_kota']).sprintf('%04d', 01);
                }else{

                }
            }else{

            }
        return $dir;

    }


    function upload_single_file($ftp,$param,$dir,$media){
        $ftp_conn = ftp_connect($ftp['host']) or die("Could not connect to ".$ftp['host']);
        $login = ftp_login($ftp_conn, $ftp['user'], $ftp['pass']);
        $fileName = $media->getClientFilename(); 
        $media->moveTo($ftp['temp_loc'].$fileName);
        $file_list = ftp_nlist($ftp_conn, $dir);
        if (in_array($fileName, $file_list)) 
        {
            return 2;
        }
        else
        {
            if (ftp_put($ftp_conn, $dir.'/'.$fileName, $ftp['temp_loc'].$fileName, FTP_BINARY)){
                return 1;
            }else{
                return 0;
            } 
        };
        
        ftp_close($ftp_conn);

    }

    $app->group('/api/v1', function () use ($app) {
        $container = $app->getContainer();

        $app->group('/komoditas', function () use ($app) {
            $komoditasContainer = $app->getContainer();
            $app->get('/sektor', function (Request $request, Response $response, array $args) use ($komoditasContainer) {
                $sql = "SELECT id_sektor, nama_sektor_komoditas FROM `komoditas_sektor`";
                $stmt = $this->db->prepare($sql);
                $respCode = 200;
                if($stmt->execute()){
                    if ($stmt->rowCount() > 0) {
                        $data = $stmt->fetchAll();
                        $result = array('STATUS' => 'SUCCESS', 'MESSAGE' => null,'DATA'=>$data);
                    }else{
                        $respCode = 404;
                        $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Data tidak ditemukan','DATA'=>null);
                    }
                }else{
                    $respCode = 500;
                    $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Error executing query','DATA'=>null);
                }
    
                $newResponse = $response->withJson($result,$respCode);
                return $newResponse;
            });
            $app->get('/sektor/{id_sektor}/kelompok', function (Request $request, Response $response, array $args) use ($komoditasContainer) {
                $id_sektor = $args["id_sektor"];
                $sql = "SELECT id_kelompok,nama_kelompok,komoditas_kelompok.id_sektor,nama_sektor_komoditas FROM `komoditas_kelompok` 
                        join komoditas_sektor on komoditas_kelompok.id_sektor = komoditas_sektor.id_sektor
                        WHERE komoditas_sektor.id_sektor = :id_sektor";
                $stmt = $this->db->prepare($sql);
                $data = [":id_sektor" => $id_sektor];
                $respCode = 200;
                if($stmt->execute($data)){
                    if ($stmt->rowCount() > 0) {
                        $data = $stmt->fetchAll();
                        $result = array('STATUS' => 'SUCCESS', 'MESSAGE' => null,'DATA'=>$data);
                    }else{
                        $respCode = 404;
                        $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Data tidak ditemukan','DATA'=>null);
                    }
                }else{
                    $respCode = 500;
                    $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Error executing query','DATA'=>null);
                }
    
                $newResponse = $response->withJson($result,$respCode);
                return $newResponse;
            });
            $app->get('/sektor/{id_sektor}/kelompok/{id_kelompok}', function (Request $request, Response $response, array $args) use ($komoditasContainer) {
                $id_sektor = $args["id_sektor"];
                $id_kelompok = $args["id_kelompok"];
                $sql = "SELECT * FROM `master_komoditas` a 
                        join komoditas_kelompok b on a.id_kelompok = b.id_kelompok and a.id_sektor = b.id_sektor
                        join komoditas_sektor c on b.id_sektor = c.id_sektor
                        where a.id_kelompok = :id_kelompok
                        and a.id_sektor = :id_sektor";
                $stmt = $this->db->prepare($sql);
                $data = [":id_sektor" => $id_sektor,":id_kelompok" => $id_kelompok];

                $respCode = 200;
                if($stmt->execute($data)){
                    if ($stmt->rowCount() > 0) {
                        $data = $stmt->fetchAll();
                        $result = array('STATUS' => 'SUCCESS', 'MESSAGE' => null,'DATA'=>$data);
                    }else{
                        $respCode = 404;
                        $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Data tidak ditemukan','DATA'=>null);
                    }
                }else{
                    $respCode = 500;
                    $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Error executing query','DATA'=>null);
                }
    
                $newResponse = $response->withJson($result,$respCode);
                return $newResponse;
            });
        });

        $app->group('/layanan', function () use ($app) {
            $layananContainer = $app->getContainer();
            $app->post('/{id_usaha}/unggah_dokumen/{id_layanan}', function (Request $request, Response $response, array $args) use ($layananContainer) {
                $dokumen = $request->getParsedBody();
                $id_layanan = $args["id_layanan"];
                $id_usaha = $args["id_usaha"];

                $jenis = $dokumen['jenis'];
                $limit = 0;

                $sqlDok = "SELECT * FROM `detail_dokumen` a join master_dokumen b on a.kode_dokumen=b.kode_dokumen where kode_layanan = :jenis";
                $stmtDok = $this->db->prepare($sqlDok);
                $dataDok = [
                    ":jenis" => $jenis
                ];
                $uploads = array();
                $id_dokumen = array();
                $limit = 0;
                if($stmtDok->execute($dataDok)){
                    if ($stmtDok->rowCount() > 0) {
                        $dataDocs = $stmtDok->fetchAll();
                        $i = 0;
                        foreach ($dataDocs as $dataDocs){
                            $uploads[$i] = $dataDocs['nama_dokumen'];
                            $id_dokumen[$i] = $dataDocs['kode_dokumen'];
                            $i++;
                        }
                        $limit = $i++;
                    }else{
                        return $response->withJson(array('STATUS' => 'FAILED', 'MESSAGE' => 'Data master dokumen belum lengkap','DATA'=>null),404);
                    }
                }else{
                    return $response->withJson(array('STATUS' => 'FAILED', 'MESSAGE' => 'Failed to fetch data','DATA'=>null),500);
                }

                $uploadedFiles = $request->getUploadedFiles();

                $sql = "SELECT distinct a.nama_usaha,b.nama_lengkap,b.kode_kota,b.id_user FROM `identitas_usaha` a join user b on a.id_user = b.id_user 
                        where a.id_identitas_usaha = :id_usaha";
                $stmt = $this->db->prepare($sql);
    
                $data = [
                    ":id_usaha" => $id_usaha
                ];

                $nama_usaha = 'XXX';
                $dir = "";
                if($stmt->execute($data)){
                    if ($stmt->rowCount() > 0) {
                        $data = $stmt->fetch();
                        $dir = strtoupper(explode(" ", $data['nama_lengkap'])[0]).preg_replace('/\./', '', $data['kode_kota']).sprintf('%04d', 01);
                        $nama_usaha = str_replace(" ","X",$data['nama_usaha']);
                    }else{

                    }
                }else{
                    $respCode = 500;
                    $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Error executing query','DATA'=>null);
                }
                
				$arr_nama_usaha = str_split($nama_usaha);
				$jml_arr = sizeof($arr_nama_usaha);
				$kode= '';
				for ($i=0; $i < 3; $i++) {
					$kode.=$arr_nama_usaha[rand(0,$jml_arr-1)];
				}
				$timestamp = time();
                $kode =  strtoupper($kode).date('Ymd').substr($timestamp,7,3);

                if(sizeof($uploadedFiles['gambar']) < $limit){
                    $respCode = 404;
                    return $response->withJson(array('STATUS' => 'FAILED', 'MESSAGE' => 'Dokumen belum lengkap','DATA'=>null),$respCode);
                }

                $ftp = $this->ftp;
                $ftp_conn = ftp_connect($ftp['host']) or die("Could not connect to ".$ftp['host']);
                $login = ftp_login($ftp_conn, $ftp['user'], $ftp['pass']); 
                $uploadedFiles = $request->getUploadedFiles();

                $isSuccess = true;
                $i = 0;
                foreach($uploadedFiles['gambar'] as $upflie){
                    $fileName = $upflie->getClientFilename();  
                    $upflie->moveTo($ftp['temp_loc'].$fileName);
                    $file_list = ftp_nlist($ftp_conn, ".");
                    $isExist = false;
                    foreach($file_list as $file_list){
                        if($file_list == $dir){
                            $isExist = true;
                        }
                    }
                    if($isExist == false){
                        if (ftp_mkdir($ftp_conn, $dir)){

                        }
                    }

                    if (ftp_put($ftp_conn, $dir.'/'.$fileName, $ftp['temp_loc'].$fileName, FTP_BINARY)){
                        $sqlIns = "INSERT INTO dokumen_layanan (id_dokumen, file,nama_dokumen,id_layanan,mime_type) 
                                    VALUES (:id_dokumen,:file,:nama_dokumen,:id_layanan, :mime_type)";
                                    
                        $stmtIns = $this->db->prepare($sqlIns);
                        $dataIns = [
                            ":id_dokumen" => null,
                            ":file" => $fileName,
                            ":nama_dokumen" => $uploads[$i],
                            ":id_layanan" => $id_layanan,
                            ":mime_type" => null
                        ];
                        if($stmtIns->execute($dataIns)){
                            $last_id = $this->db->lastInsertId();
                        }else{
                            $isSuccess = false;
                            $respCode = 500;
                        }
                    }
                    else{
                        return $response->withJson(array('STATUS' => 'FAILED', 'MESSAGE' => 'Dokumen belum lengkap','DATA'=>null),$respCode);

                    }
                    unlink($ftp['temp_loc'].$fileName);
                    $i++;
                }
                ftp_close($ftp_conn);
                if ($isSuccess) {
                    $sqlUpdate = "UPDATE layanan SET syarat_teknis = :syarat_teknis, kode_pendaftaran = :kode_pendaftaran WHERE uid = :id_layanan";
                    $stmtUpdate = $this->db->prepare($sqlUpdate);
                    $dataUpdate = [":syarat_teknis" => "-",":kode_pendaftaran" => $kode, ":id_layanan"=>$id_layanan];

                    $respCode = 200;
                    if($stmtUpdate->execute($dataUpdate)){
                        if ($stmt->rowCount() > 0) {
                            $result = array('STATUS' => 'SUCCESS', 'MESSAGE' => 'Dokumen berhasil disimpan','DATA'=>null);
                        }else{
                            $respCode = 404;
                            $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Data tidak ditemukan','DATA'=>null);
                        }
                    }else{
                        $respCode = 500;
                        $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Error executing query','DATA'=>null);
                    }
                }else{
                    $respCode = 500;
                    $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Gagal menyimpan dokumen','DATA'=>null);
                }

                $newResponse = $response->withJson($result,$respCode);
                return $newResponse;
                
            });

            $app->post('/{id_usaha}/daftar/{jenis}', function (Request $request, Response $response, array $args) use ($layananContainer) {
                $id_usaha = $args["id_usaha"];
                $jenis = $args["jenis"];
                $raw_data = $request->getParsedBody();
                $last_id = 0;

                $sql = "INSERT INTO layanan (UID, KODE_LAYANAN, ID_IDENTITAS_USAHA) VALUES (:a,:b,:c)";
                $stmt = $this->db->prepare($sql);
                $data = [
                    ":a" => null,
                    ":b" => $jenis,
                    ":c" => $id_usaha
                ];
                if($stmt->execute($data)){
                    $last_id = $this->db->lastInsertId();
                }else{
                    $respCode = 500;
                    return $response->withJson(array('STATUS' => 'FAILED', 'MESSAGE' => 'Error inserting data','DATA'=>null),$respCode);
                }
                $msg = "";
                if($jenis == 'prima_3' ){
                    $msg = "PRIMA 3";
                }
                else if($jenis == 'prima_2' ){
                    $msg = "PRIMA 2";
                }
                $result = array('STATUS' => 'SUCCESS', 'MESSAGE' => 'Pendaftaran layanan '.$msg.' berhasil','DATA'=>null);
                $respCode = 200;
                    
                if($jenis == 'prima_3' || $jenis == 'prima_2'){
                    foreach($raw_data as $data_komoditas){
                        $sql_komoditas = "INSERT INTO detail_komoditas (id_detail, id_komoditas, id_kelompok, id_sektor,luas_lahan,nama_latin,id_layanan,nama_jenis_komoditas) 
                        VALUES (:id_detail,:id_komoditas,:id_kelompok,:id_sektor,:luas_lahan,:nama_latin,:id_layanan,:nama_jenis_komoditas)";
                        $stmt_komoditas = $this->db->prepare($sql_komoditas);
                        $data_komoditas = [
                            ":id_detail" => null,
                            ":id_komoditas" => $data_komoditas["kode_komoditas"],
                            ":id_kelompok" => $data_komoditas["id_kelompok"],
                            ":id_sektor" => $data_komoditas["id_sektor"],
                            ":luas_lahan" => $data_komoditas["luas_lahan"],
                            ":nama_latin" => $data_komoditas["nama_latin"],
                            ":id_layanan" => $last_id,
                            "nama_jenis_komoditas" => $data_komoditas["nama_jenis_komoditas"]
                        ];
                        if($stmt_komoditas->execute($data_komoditas)){

                        }else{
                            $respCode = 200;
                            $result = array('STATUS' => 'WARNING', 'MESSAGE' => 'Pendaftaran PRIMA 3 berhasil, ada beberapa komoditas tidak dapat masuk','DATA'=>null);
                        }
                    }
                }
                $newResponse = $response->withJson($result,$respCode);
                return $newResponse;
            });
        });

        $app->post('/login', function (Request $request, Response $response, array $args) use ($container) {
            $user = $request->getParsedBody();
            $password = sha1('Okkpd2018!'.$user['password']);
            $username = $user['username'];
            $role = $user['role'];
           
            $sql = "SELECT username, nama_lengkap,alamat_lengkap,kode_kota,kode_role,id_user FROM user WHERE username =:username AND password=:password and kode_role =:role";
            $stmt = $this->db->prepare($sql);

            $data = [
                ":username" => $username,
                ":password" => $password,
                ":role" => $role
            ];
            $respCode = 200;
            if($stmt->execute($data)){
                if ($stmt->rowCount() > 0) {
                    $data = $stmt->fetch();
                    $ftp = $this->ftp;
                    $ftp_conn = ftp_connect($ftp['host']) or die("Could not connect to ".$ftp['host']);
                    $login = ftp_login($ftp_conn, $ftp['user'], $ftp['pass']); 
                    $file_list = ftp_nlist($ftp_conn, ".");
                    $isExist = false;
                    $dir = strtoupper(explode(" ", $data['nama_lengkap'])[0]).preg_replace('/\./', '', $data['kode_kota']).sprintf('%04d', 01);

                    foreach($file_list as $file_list){
                        if($file_list == $dir){
                            $isExist = true;
                        }
                    }

                    if($isExist == false){
                        if (ftp_mkdir($ftp_conn, $dir)){
                        }
                    }

                    $result = array('STATUS' => 'SUCCESS', 'MESSAGE' => null,'DATA'=>$dir);
                }else{
                    $respCode = 404;
                    $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'User atau password tidak sesuai','DATA'=>null);
                }
            }else{
                $respCode = 500;
                $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Error executing query','DATA'=>null);
            }

            $newResponse = $response->withJson($result,$respCode);
            return $newResponse;
        });
        $app->group('/user', function () use ($app) {
            $userContainer = $app->getContainer();
            $app->get('/{id_user}', function (Request $request, Response $response, array $args) use ($userContainer) {
                $id_user = $args["id_user"];

                $sql = "SELECT username, nama_lengkap,alamat_lengkap,kode_role,id_user FROM user WHERE id_user = :id_user";
                $stmt = $this->db->prepare($sql);
    
                $data = [
                    ":id_user" => $id_user
                ];
                $respCode = 200;
                if($stmt->execute($data)){
                    if ($stmt->rowCount() > 0) {
                        $data = $stmt->fetch();
                        $result = array('STATUS' => 'SUCCESS', 'MESSAGE' => null,'DATA'=>$data);
                    }else{
                        $respCode = 404;
                        $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'User atau password tidak sesuai','DATA'=>null);
                    }
                }else{
                    $respCode = 500;
                    $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Error executing query','DATA'=>null);
                }
    
                $newResponse = $response->withJson($result,$respCode);
                return $newResponse;
            });

            $app->get('/{id_user}/media', function (Request $request, Response $response, array $args) use ($userContainer) {
                $id_user = $args["id_user"];

                $sql = "SELECT c.nama_media,c.mime_type,c.date_upload FROM user a JOIN user_media c ON a.id_user = c.id_user
                WHERE a.id_user = :id_user order by c.date_upload desc";
                $stmt = $this->db->prepare($sql);
    
                $data = [
                    ":id_user" => $id_user
                ];

                $respCode = 200;
                if($stmt->execute($data)){
                    if ($stmt->rowCount() > 0) {
                        $data = $stmt->fetchAll();
                        $result = array('STATUS' => 'SUCCESS', 'MESSAGE' => null,'DATA'=>$data);
                    }else{
                        $respCode = 404;
                        $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Tidak ditemukan data media','DATA'=>null);
                    }
                }else{
                    $respCode = 500;
                    $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Error executing query','DATA'=>null);
                }
    
                $newResponse = $response->withJson($result,$respCode);
                return $newResponse;
            });

            $app->post('/{id_user}/media', function (Request $request, Response $response, array $args) use ($userContainer) {
                $id_user = $args["id_user"];

                $sql = "SELECT * FROM user a JOIN user_media c ON a.id_user = c.id_user
                WHERE a.id_user = :id_user";
                $stmt = $this->db->prepare($sql);
    
                $data = [
                    ":id_user" => $id_user
                ];

                $respCode = 200;
                if($stmt->execute($data)){
                    $ftp = $this->ftp;
                    $uploadedFiles = $request->getUploadedFiles();
                    $media = $uploadedFiles["media"];
                    $dir = getDir($this->db,$id_user);
                    $uploadProses = upload_single_file($ftp,"media",$dir,$uploadedFiles["media"]);
                    if($uploadProses == 1){
                        $sql_media = "INSERT INTO user_media (id_user, nama_media, mime_type) 
                                    VALUES (:id_user,:nama_media,:mime_type)";
                        $stmt_media = $this->db->prepare($sql_media);
                        $data_media = [
                            ":id_user" => $id_user,
                            ":nama_media" => $media->getClientFilename(),
                            ":mime_type" => pathinfo($media->getClientFilename(), PATHINFO_EXTENSION)
                        ];
                        if($stmt_media->execute($data_media)){
                            $respCode = 200;
                            $result = array('STATUS' => 'SUCCESS', 'MESSAGE' => 'Data berhasil diunggah','DATA'=>null);
                        }else{
                            $respCode = 200;
                            $result = array('STATUS' => 'WARNING', 'MESSAGE' => 'Dokumen tidak dapat diunggah','DATA'=>null);
                        }
                    }else if($uploadProses == 2){
                        $respCode = 400;
                        $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'File sudah ada pada direktori anda','DATA'=>null);
                    }else{
                        $respCode = 500;
                        $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Data tidak dapat diunggah','DATA'=>null);
                    }
                }else{
                    $respCode = 500;
                    $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Error executing query','DATA'=>null);
                }
    
                $newResponse = $response->withJson($result,$respCode);
                return $newResponse;
            });

            $app->get('/{id_user}/usaha', function (Request $request, Response $response, array $args) use ($userContainer) {
                $id_user = $args["id_user"];

                $sql = "SELECT a.id_identitas_usaha,a.nama_pemohon,a.jabatan_pemohon,a.no_ktp_pemohon,COALESCE(a.foto_ktp,'Foto KTP belum diunggah') foto_ktp,a.no_npwp,a.nama_usaha,
                a.alamat_usaha,a.rt,a.rw,a.kelurahan,a.kecamatan,a.kota, a.no_telp,a.unit_kerja,a.jenis_usaha FROM identitas_usaha a JOIN user b ON a.id_user = b.id_user
                WHERE a.id_user = :id_user";
                $stmt = $this->db->prepare($sql);
    
                $data = [
                    ":id_user" => $id_user
                ];
                $respCode = 200;
                if($stmt->execute($data)){
                    if ($stmt->rowCount() > 0) {
                        $data = $stmt->fetchAll();
                        $result = array('STATUS' => 'SUCCESS', 'MESSAGE' => null,'DATA'=>$data);
                    }else{
                        $respCode = 404;
                        $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Anda belum mengisi identitas usaha','DATA'=>null);
                    }
                }else{
                    $respCode = 500;
                    $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Error executing query','DATA'=>null);
                }
    
                $newResponse = $response->withJson($result,$respCode);
                return $newResponse;
            });

            $app->get('/{id_user}/usaha/{id_usaha}', function (Request $request, Response $response, array $args) use ($userContainer) {
                $id_user = $args["id_user"];
                $id_usaha = $args["id_usaha"];

                $sql = "SELECT a.id_identitas_usaha,a.nama_pemohon,a.jabatan_pemohon,a.no_ktp_pemohon,COALESCE(a.foto_ktp,'Foto KTP belum diunggah') foto_ktp,a.no_npwp,a.nama_usaha,
                a.alamat_usaha,a.rt,a.rw,a.kelurahan,a.kecamatan,a.kota, a.no_telp,a.unit_kerja,a.jenis_usaha FROM identitas_usaha a JOIN user b ON a.id_user = b.id_user
                WHERE a.id_user = :id_user and a.id_identitas_usaha = :id_usaha";
                $stmt = $this->db->prepare($sql);
    
                $data = [
                    ":id_user" => $id_user,
                    ":id_usaha" => $id_usaha
                ];
                $respCode = 200;
                if($stmt->execute($data)){
                    if ($stmt->rowCount() > 0) {
                        $data = $stmt->fetch();
                        $result = array('STATUS' => 'SUCCESS', 'MESSAGE' => null,'DATA'=>$data);
                    }else{
                        $respCode = 404;
                        $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Data tidak ditemukan','DATA'=>null);
                    }
                }else{
                    $respCode = 500;
                    $result = array('STATUS' => 'FAILED', 'MESSAGE' => 'Error executing query','DATA'=>null);
                }
    
                $newResponse = $response->withJson($result,$respCode);
                return $newResponse;
            });
        });
    });


};
