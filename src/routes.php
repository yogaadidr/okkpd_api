<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    

    $app->group('/api/v1', function () use ($app) {
    $container = $app->getContainer();

        $app->post('/login', function (Request $request, Response $response, array $args) use ($container) {
            $user = $request->getParsedBody();
            $password = sha1($user['password']);
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
    });
};
