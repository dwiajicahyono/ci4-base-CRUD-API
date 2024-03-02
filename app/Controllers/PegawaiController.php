<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class PegawaiController extends ResourceController
{
    protected $modelName = 'App\models\Pegawai';
    protected $format = 'json';
    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return ResponseInterface
     */
    // fungsi agar tidak ngulang validasi
    function validasirule($param)
    {
        return [
            'rules' => "required|is_unique[pegawai.$param]",
            'errors' => [
                'required' => '{field} harus diisi.',
                'is_unique' => '{field} tersebut sudah terdaftar'
            ]
        ];
    }
    // validasi khusus alamat dan jabatan dibedakan
    function validasibersama()
    {
        return [
            'rules' => "required",
            'errors' => [
                'required' => '{field} harus diisi.',
            ]
        ];
    }

    // fungsi untuk upload gambar 
    private function saveImage($file)
    {
        // Tentukan path folder penyimpanan gambar
        $basePath = FCPATH . 'img/'; // Sesuaikan dengan path yang diinginkan

        // Jika folder "img" belum ada, buat folder tersebut
        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }

        // Generate nama unik untuk file gambar
        $imageName = md5(uniqid(rand(), true)) . '.' . $file->getClientExtension();

        // Pindahkan file gambar ke folder penyimpanan
        $file->move($basePath, $imageName);

        // Kembalikan path atau nama file yang dapat disimpan dalam database
        return $imageName;
    }

    public function index()
    {
        $pegawaiModel = new \App\Models\Pegawai();
        $data_pegawai = $pegawaiModel->findAll();
        $totalData = count($data_pegawai);
        $data = [
            'message' => 'sukses',
            'count' => $totalData,
            'data_pegawai' => $this->model->orderBy('id', 'desc')->findAll(), //untuk mengurutkan data dari yang paling baru
            // 'data_pegawai' => $this->model->findAll()
        ];
        return $this->respond($data, 200);
    }

    /**
     * Return the properties of a resource object.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function show($id = null)
    {
        $data = [
            'message' => 'sukses',
            'databyid' => $this->model->find($id),
        ];
        if ($data['databyid'] == null) {
            return $this->failNotFound('data pegawai tidak ditemukan');
        }

        return $this->respond($data, 200);
    }
    /**
     * Create a new resource object, from "posted" parameters.
     *
     * @return ResponseInterface
     */
    public function create()
    {
        // validasi opsional 
        $rules = $this->validate([
            'nama' => $this->validasirule('nama'),
            'alamat' => $this->validasibersama(),
            'jabatan' => $this->validasibersama(),
            'email' => $this->validasirule('email'),
            'gambar' => [
                'rules' => 'uploaded[gambar]|max_size[gambar,1024]|mime_in[gambar,image/jpg,image/jpeg,image/png]',
                'errors' => [
                    'uploaded' => 'Silahkan pilih file {field}.',
                    'max_size' => 'Ukuran file {field} tidak boleh lebih dari 1MB.',
                    'mime_in' => 'Format file {field} harus jpg, jpeg, atau png.'
                ]
            ],
        ]);
        if (!$rules) {
            $response = [
                'message' => $this->validator->getErrors()
            ];
            return $this->failValidationErrors($response);
        }
        // Simpan gambar dan dapatkan nama file yang disimpan
        $imageName = $this->saveImage($this->request->getFile('gambar'));
        // Simpan data ke database
        $this->model->insert([
            'nama' => esc($this->request->getVar('nama')),
            'alamat' => esc($this->request->getVar('alamat')),
            'jabatan' => esc($this->request->getVar('jabatan')),
            'email' => esc($this->request->getVar('email')),
            'gambar' => $imageName, // Simpan nama file gambar dalam database
        ]);
        $response = [
            'message' => 'data sudah ditambahkan'
        ];
        return $this->respondCreated($response);
    }



    /**
     * Add or update a model resource, from "posted" properties.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function update($id = null)
    {
        log_message('info', "Received request to update with ID: {$id}");

        // Cek apakah ID diterima
        if (is_null($id) || $id === '') {
            return $this->failNotFound('ID tidak diberikan atau tidak valid');
        }

        $dataLama = $this->model->find($id);
        if (!$dataLama) {
            log_message('error', "Data tidak ditemukan untuk ID: {$id}");
            return $this->failNotFound('Data tidak ditemukan');
        }

        $dataLama = $this->model->find($id);
        if (!$dataLama) {
            return $this->failNotFound('Data tidak ditemukan');
        }

        // Ambil data dari request
        $nama = $this->request->getPost('nama') ?: $dataLama['nama'];
        $alamat = $this->request->getPost('alamat') ?: $dataLama['alamat'];
        $jabatan = $this->request->getPost('jabatan') ?: $dataLama['jabatan'];
        $email = $this->request->getPost('email') ?: $dataLama['email'];

        // Siapkan array untuk update
        $updateData = [
            'nama' => esc($nama),
            'alamat' => esc($alamat),
            'jabatan' => esc($jabatan),
            'email' => esc($email),
        ];

        // Cek dan proses file gambar jika ada
        $file = $this->request->getFile('gambar');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            // Jika ada file gambar yang di-upload, proses dan update nama file gambar
            $newImageName = $this->saveImage($file);

            // Tambahkan nama gambar baru ke array updateData
            $updateData['gambar'] = $newImageName;

            // Opsional: Hapus gambar lama jika gambar baru berhasil di-upload
            $oldImagePath = FCPATH . 'img/' . $dataLama['gambar'];
            if (file_exists($oldImagePath) && is_file($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Lakukan update
        if ($this->model->update($id, $updateData)) {
            return $this->respondUpdated(['message' => 'Data berhasil diupdate']);
        } else {
            return $this->failServerError('Gagal mengupdate data');
        }
    }


    /**
     * Delete the designated resource object from the model.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function delete($id = null)
    {
        $modelGambar = $this->model->find($id);

        if ($modelGambar) {
            // Mendapatkan nama file gambar
            $namaGambar = $modelGambar['gambar'];

            // Path lengkap untuk file gambar
            $gambarPath = FCPATH . 'img/' . $namaGambar;

            // Menghapus file sampul dari direktori
            if (file_exists($gambarPath)) {
                unlink($gambarPath);
            } else {
                // Log atau tampilkan pesan kesalahan jika file tidak ditemukan
                log_message('error', 'File gambar tidak ditemukan: ' . $gambarPath);
            }

            // Hapus data dari database
            $this->model->delete($id);
            $response = [
                'message' => 'data sudah dihapus'
            ];

            return $this->respondDeleted($response);
        } else {
            // Log atau tampilkan pesan kesalahan jika data tidak ditemukan
            log_message('error', 'Data tidak ditemukan: ' . $id);
            return $this->failNotFound('Data tidak ditemukan');
        }
    }
}
