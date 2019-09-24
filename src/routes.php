<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    

    $app->group('/api/v1', function () use ($app) {
    $container = $app->getContainer();

        $app->post('/login', function (Request $request, Response $response, array $args) use ($container) {
            $user = $request->getParsedBody();
            $password = sha1('Okkpd2018!'.$user['password']);
            $username = $user['username'];
            $role = $user['role'];
           
            $sql = "SELECT username, nama_lengkap,alamat_lengkap,kode_role,id_user FROM user WHERE username =:username AND password=:password and kode_role =:role";
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

    });
};
