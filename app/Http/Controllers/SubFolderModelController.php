<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubFolderModelRequest;
use App\Models\FolderModel;
use App\Models\SubFolderModel;
use App\Models\DocumentsModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;


class SubFolderModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $page = $request->input('page', 1);

        $folderId = $request->input('folder_id');

        $subfolderId = $request->input('subfolder_id');

        $query = SubFolderModel::query();

        if($subfolderId){
            $query->where('id', $subfolderId);
        }

        if($folderId){
            $query->where('id_folder', $folderId);
        }

        $subfolders = $query->paginate($perPage, ['*'], 'page', $page);

        $response = [
            'items' => $subfolders->items(),
            'page' => $subfolders->currentPage(),
            'per_page' => $subfolders->perPage(),
            'totalCounts' => $subfolders->total(),
        ];

        return response()->json($response);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SubFolderModelRequest $request)
    {
        try{
            $folder = FolderModel::find($request->input('id_folder'));

            if(!$folder){
                return response()->json(['error' => 'La carpeta no existe en la base de datos'], 404);
            }

            $folderPath = public_path('folders/'. $folder->folder_name . '/' . $request->input('subfolder_name'));

            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            } else {
                return response()->json([
                    'error' => 'La subcarpeta ya existe en el servidor'
                ], 400);
            }

            $subFolder = new SubFolderModel();
            $subFolder->subfolder_name = $request->input('subfolder_name');
            $subFolder->id_folder = $request->input('id_folder');
            $subFolder->save();

            return response()->json(['message' => 'Subcarpeta creada correctamente', 'subfolder' => $subFolder], 201);

        }catch(Exception $ex){
            return response()->json([
                'error' => 'Fallo la creacion de la subcarpeta: ' . $ex->getMessage(),
            ], 500);   
        }


    }

    /**
     * Display the specified resource.
     */
    public function show(Int $id)
    {
        try {
            $subFolderModel = SubFolderModel::find($id);

            if (!$subFolderModel) {
                return response()->json(['error' => 'La subcarpeta no existe en la base de datos'], 404);
            }

            $folder = FolderModel::find($subFolderModel->id_folder);
            $subFolderPath = public_path('folders/' . $folder->folder_name . '/' . $subFolderModel->subfolder_name);

            $existsOnServer = file_exists($subFolderPath);

            $response = [
                'id' => $subFolderModel->id,
                'subfolder_name' => $subFolderModel->subfolder_name,
                'exists_on_server' => $existsOnServer,
                'created_at' => $subFolderModel->created_at,
                'updated_at' => $subFolderModel->updated_at,
            ];

            return response()->json($response, 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al obtener detalles de la subcarpeta: ' . $e->getMessage()], 500);
        }    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SubFolderModelRequest $request, Int $id)
    {
        try {
            $subFolderModel = SubFolderModel::find($id);

            if (!$subFolderModel) {
                return response()->json(['error' => 'La subcarpeta no existe en la base de datos'], 404);
            }

            $folder = FolderModel::find($subFolderModel->id_folder);
            $newSubFolderName = $request->input('subfolder_name');

            $currentSubFolderPath = public_path('folders/' . $folder->folder_name . '/' . $subFolderModel->subfolder_name);
            $newSubFolderPath = public_path('folders/' . $folder->folder_name . '/' . $newSubFolderName);

            if ($subFolderModel->subfolder_name !== $newSubFolderName) {
                if (!File::move($currentSubFolderPath, $newSubFolderPath)) {
                    return response()->json(['error' => 'No se pudo renombrar la subcarpeta en el servidor'], 500);
                }
            }

            $subFolderModel->subfolder_name = $newSubFolderName;
            $subFolderModel->save();

            return response()->json(['message' => 'Subcarpeta actualizada correctamente'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al actualizar la subcarpeta: ' . $e->getMessage()], 500);
        }    
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Int $id)
    {
        try {
            $subFolderModel = SubFolderModel::find($id);

            if (!$subFolderModel) {
                return response()->json(['error' => 'La subcarpeta no existe en la base de datos'], 404);
            }

            $folder = FolderModel::find($subFolderModel->id_folder);
            $subFolderPath = public_path('folders/' . $folder->folder_name . '/' . $subFolderModel->subfolder_name);

            if (!file_exists($subFolderPath)) {
                $subFolderModel->delete();
                return response()->json(['error' => 'La subcarpeta no existe en el servidor a ' . $subFolderModel->subfolder_name], 200);
            }
            else{
                $documents = DocumentsModel::where('id_subfolder', $subFolderModel->id)->get();
                
                if(!$documents->isEmpty()){
                    foreach ($documents as $document) {
                        $documentPath = $subFolderPath . '/' . $document->document_name . '.' . $document->document_type;
                        if (file_exists($documentPath)) {
                            File::delete($documentPath);
                        }
                        $document->delete();
                    }
                }
            }

            $deleted = File::deleteDirectory($subFolderPath);

            if (!$deleted) {
                return response()->json(['error' => 'No se pudo eliminar la subcarpeta del servidor'], 500);
            }

            $subFolderModel->delete();

            return response()->json(['message' => 'Subcarpeta borrada correctamente'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al borrar la subcarpeta: ' . $e->getMessage()], 500);
        }    
    }
}
