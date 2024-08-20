<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentsModelRequest;
use App\Models\DocumentsModel;
use App\Models\FolderModel;
use App\Models\SubFolderModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;


class DocumentsModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $subfolderId = $request->input('subfolder_id');
        $type = $request->input('type_file');
        $dateAsc = $request->input('date_asc');
        $dateDesc = $request->input('date_desc');
        $nameAsc = $request->input('name_asc');
        $nameDesc = $request->input('name_desc');
        $q = $request->input('q');

        $query = DocumentsModel::query();

        if ($subfolderId) {
            $query->where('id_subfolder', $subfolderId);
        }

        if ($q) {
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('document_name', 'like', '%' . $q . '%')
                    ->orWhere('document_type', 'like', '%' . $q . '%');
            });
        }

        if ($type) {
            $query->where('document_type', $type);
        }

        if ($dateAsc) {
            $query->orderBy('created_at', 'asc');
        }

        if ($dateDesc) {
            $query->orderBy('created_at', 'desc');
        }

        if ($nameAsc) {
            $query->orderBy('document_name', 'asc');
        }

        if ($nameDesc) {
            $query->orderBy('document_name', 'desc');
        }

        $documents = $query->paginate($perPage, ['*'], 'page', $page);

        $response = [
            'items' => $documents->items(),
            'page' => $documents->currentPage(),
            'per_page' => $documents->perPage(),
            'totalCounts' => $documents->total(),
        ];

        return response()->json($response);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(DocumentsModelRequest $request)
    {
        try {

            $subfolder = SubFolderModel::find($request->input('id_subfolder'));

            if(!$subfolder){
                return response()->json(['error' => 'La subcarpeta no existe en la base de datos'], 404);
            }
            
            $folder = FolderModel::find($subfolder->id_folder);
            
            if(!$folder){
                return response()->json(['error' => 'La carpeta principal no existe en la base de datos'], 404);
            }

            $folderPath = public_path('folders/' . $folder->folder_name . '/' . $subfolder->subfolder_name);
            if (!File::exists($folderPath)) {
                return response()->json(['error' => 'La subcarpeta no existe en el servidor'], 404);
            }


            $file = $request->file('document_content');

            //$date = now()->format('dmy-His');
            //$documentName = $subfolder->subfolder_name.' '.$date;

            $documentName = $request->input('document_name'); // Obtener el nombre del documento desde la solicitud
            $documentExtension = $file->getClientOriginalExtension();
            
            $documentPath = $folderPath . '/' . $documentName.'.'.$documentExtension;

            $file->move($folderPath, $documentName.'.'.$documentExtension);

            $document = new DocumentsModel();
            $document->document_name = $documentName;
            $document->document_type = $documentExtension;
            $document->document_content = base64_encode(file_get_contents($documentPath));
            $document->id_subfolder = $request->input('id_subfolder');
            $document->id_user = $request->input('id_user');
            $document->save();

            return response()->json(['message' => 'Documento creado correctamente', 'document' => $document], 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al crear el documento: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Int $id)
    {
        try {
            $document = DocumentsModel::find($id);

            if (!$document) {
                return response()->json(['error' => 'El documento no existe en la base de datos'], 404);
            }

            return response()->json($document, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al obtener el documento: ' . $e->getMessage()], 500);
        }    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DocumentsModelRequest $request, Int $id)
{
    try {
        
        $document = DocumentsModel::find($id);
       
        if (!$document) {
            return response()->json(['error' => 'El documento no existe en la base de datos'], 404);
        }

        $subfolder = SubFolderModel::find($document->id_subfolder);

        if(!$subfolder){
            return response()->json(['error' => 'La subcarpeta no existe en la base de datos'], 404);
        }
        
        $folder = FolderModel::find($subfolder->id_folder);
        
        if(!$folder){
            return response()->json(['error' => 'La carpeta principal no existe en la base de datos'], 404);
        }
        
        $folderPath = public_path('folders/' . $folder->folder_name . '/' . $subfolder->subfolder_name);
        
        if (!File::exists($folderPath)) {
            return response()->json(['error' => 'La subcarpeta no existe en el servidor'], 404);
        }
        
        $file = $request->file('document_content');
        
        // Obtener el nombre del documento desde la solicitud, o usar el nombre actual si no se proporciona
        $documentName = $request->input('document_name', $document->document_name);
        $documentExtension = $file->getClientOriginalExtension();
        
        $documentPath = $folderPath . '/' . $documentName . '.' . $documentExtension;

        $file->move($folderPath, $documentName.'.'.$documentExtension);
        
        // Eliminar el archivo antiguo si el nombre o la extensión han cambiado
        $oldPath = $folderPath . '/' . $document->document_name . '.' . $document->document_type;
        if (File::exists($oldPath) && ($document->document_name !== $documentName || $document->document_type !== $documentExtension)) {
            File::delete($oldPath);
        }
        
        // Actualizar los datos del documento en la base de datos
        $document->document_name = $documentName;
        $document->document_type = $documentExtension;
        $document->document_content = base64_encode(file_get_contents($documentPath));
        $document->id_subfolder = $request->input('id_subfolder');
        $document->id_user = $request->input('id_user');
        $document->save();
        
        return response()->json(['message' => 'Documento actualizado correctamente', 'document' => $document], 200);
        
    } catch (Exception $e) {
        return response()->json(['error' => 'Fallo al actualizar el documento: ' . $e->getMessage()], 500);
    }
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Int $id)
    {
        try {
            $document = DocumentsModel::find($id);

            if (!$document) {
                return response()->json(['error' => 'El documento no existe en la base de datos'], 404);
            }

            $subfolder = SubFolderModel::find($document->id_subfolder);

            if(!$subfolder){
                return response()->json(['error' => 'La subcarpeta no existe en la base de datos'], 404);
            }
            
            $folder = FolderModel::find($subfolder->id_folder);
            
            if(!$folder){
                return response()->json(['error' => 'La carpeta principal no existe en la base de datos'], 404);
            }

            $folderPath = public_path('folders/' . $folder->folder_name . '/' . $subfolder->subfolder_name);
            $documentPath = $folderPath . '/' . $document->document_name. '.' . $document->document_type;
            if (!file_exists($documentPath)) {
                $document->delete();
                return response()->json(['error' => 'La subcarpeta no existe en el servidor a ' . $document->document_name], 500);
            }

            if (file_exists($documentPath)) {
                File::delete($documentPath);
            }

            $document->delete();

            return response()->json(['message' => 'Documento borrado correctamente'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al borrar el documento: ' . $e->getMessage()], 500);
        }    
    }
}
