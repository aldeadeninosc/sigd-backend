<?php

namespace App\Http\Controllers;

use App\Http\Requests\FolderModelRequest;
use App\Models\FolderModel;
use App\Models\DocumentsModel;
use App\Models\SubFolderModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;


class FolderModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $page = $request->input('page', 1);

        $folderId = $request->input('folder_id');

        $query = FolderModel::query();

        if ($folderId) {
            $query->where('id', $folderId);
        }

        // Paginar los resultados
        $folders = $query->paginate($perPage, ['*'], 'page', $page);

        $response = [
            'items' => $folders->items(),
            'page' => $folders->currentPage(),
            'per_page' => $folders->perPage(),
            'totalCounts' => $folders->total(),
        ];

        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FolderModelRequest $request)
    {
        try{
            $folderPath = public_path('folders/' . $request->input('folder_name'));

            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            } else {
                return response()->json([
                    'error' => 'La carpeta ya existe en el servidor'
                ], 400);
            }
    

            $folder = new FolderModel();
            $folder->folder_name = $request->input('folder_name');
            $folder->id_user = $request->input('id_user');
            $folder->save();
    
            return response()->json([
                'message' => 'Carpeta creada correctamente',
                'folder' => $folder,
            ], 201);
    
        }catch(Exception $e){
            return response()->json([
                'error' => 'Fallo la creacion de la carpeta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Int $id)
    {
        try {

            $folderModel = FolderModel::find($id);

            if (!$folderModel) {
                return response()->json(['error' => 'El folder no existe en la base de datos'], 404);
            }
    
            $folderPath = public_path('folders/' . $folderModel->folder_name);
    
            $existsOnServer = file_exists($folderPath);
    
            $response = [
                'id' => $folderModel->id,
                'folder_name' => $folderModel->folder_name,
                'exists_on_server' => $existsOnServer,
                'created_at' => $folderModel->created_at,
                'updated_at' => $folderModel->updated_at,
            ];
    
            return response()->json($response, 200);
    
        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al obtener detalles de la carpeta: ' . $e->getMessage()], 500);
        }    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FolderModelRequest $request, Int $id)
    {
        try {

            $folderModel = FolderModel::find($id);

            if (!$folderModel) {
                return response()->json(['error' => 'La carpeta no existe en la base de datos'], 404);
            }
    
            $newFolderName = $request->input('folder_name');
            $id_user = $request->input('id_user');
    
            $currentFolderPath = public_path('folders/' . $folderModel->folder_name);
            $newFolderPath = public_path('folders/' . $newFolderName);
    
            if ($folderModel->folder_name !== $newFolderName) {
                if (!File::move($currentFolderPath, $newFolderPath)) {
                    return response()->json(['error' => 'No se pudo renombrar el folder en el servidor'], 500);
                }
            }

            if($folderModel->id_user !== $id_user){
                $folderModel->id_user = $id_user;
            }
    
            $folderModel->folder_name = $newFolderName;
            $folderModel->save();
    
            return response()->json(['message' => 'Carpeta actualizada correctamente'], 200);
    
        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al actualizar la carpeta: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Int $id)
    {
        try {

            $folderModel = FolderModel::find($id);

            if (!$folderModel) {
                return response()->json(['error' => 'El folder no existe en la base de datos'], 404);
            }

            $folderPath = public_path('folders/' . $folderModel->folder_name);
    
            $subFolders = SubFolderModel::where('id_folder', $folderModel->id)->get();
            
            foreach ($subFolders as $subFolder) {
                $subFolderPath = public_path('folders/' . $folderModel->folder_name . '/' . $subFolder->subfolder_name);
                    
                $documents = DocumentsModel::where('id_subfolder', $subFolder->id)->get();
                
                foreach ($documents as $document) {
                    $documentPath = $subFolderPath . '/' . $document->document_name . '.' . $document->document_type;
                    if (file_exists($documentPath)) {
                        File::delete($documentPath);
                    }
                    $document->delete();
                }
                
                File::deleteDirectory($subFolderPath);
                $subFolder->delete();
            }
            
            if (!file_exists($folderPath)) {

                $folderModel->delete();
                return response()->json(['error' => 'La carpeta no existe en el servidor a ' . $folderModel->folder_name], 200);
            }
            
            $deleted = File::deleteDirectory($folderPath);
            
            if (!$deleted) {
                return response()->json(['error' => 'No se pudo eliminar la carpeta del servidor'], 500);
            }

            $folderModel->delete();
    
            return response()->json(['message' => 'Carpeta borrada correctamente'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al borrar la carpeta: ' . $e->getMessage()], 500);
        }    
    }
}
