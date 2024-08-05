<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use App\V1\Tool\ClientTool;

class ToolResource extends JsonResource
{

    public function toArray($request)
    {
        $Client = auth('client')->user();
        $ToolBought = 0;
        if ($Client) {
            $ClientTool = ClientTool::where("IDClient", $Client->IDClient)->where("IDTool", $this->IDTool)->first();
            if ($ClientTool) {
                $ToolBought = 1;
            }
            $ClientLanguage = LocalAppLanguage($Client->ClientAppLanguage);
            $ToolTitle = "ToolTitle" . $ClientLanguage;
            $ToolDesc = "ToolDesc" . $ClientLanguage;
        } else {
            $ClientLanguage = $request->ClientAppLanguage == 'ar' ? 'Ar' : 'En';
            $ToolTitle = "ToolTitle" . $ClientLanguage;
            $ToolDesc = "ToolDesc" . $ClientLanguage;
        }

        return [
            'IDTool'                   => $this->IDTool,
            'ToolTitle'                => $this->$ToolTitle,
            'ToolDesc'                 => $this->$ToolDesc,
            'ToolPrice'                => $this->ToolPrice,
            'ToolPoints'               => $this->ToolPoints,
            'ToolType'                 => $this->ToolType,
            'ToolBought'               => $ToolBought,
            'ToolGallery'              => $this->ToolGallery,
            'ToolProduct'              => $this->ToolProduct,
        ];
    }
}
