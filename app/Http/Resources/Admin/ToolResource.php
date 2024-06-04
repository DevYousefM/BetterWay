<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ToolResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $ToolTitle = "ToolTitle".$UserLanguage;
            $ToolDesc = "ToolDesc".$UserLanguage;
        }else{
            $ToolTitle = "ToolTitleEn";
            $ToolDesc = "ToolDescEn";
        }

        return [
            'IDTool'              => $this->IDTool,
            'ToolTitle'           => $this->$ToolTitle,
            'ToolDesc'            => $this->$ToolDesc,
            'ToolStatus'          => $this->ToolStatus,
            'ToolPrice'           => $this->ToolPrice,
            'ToolPoints'          => $this->ToolPoints,
            'ToolType'            => $this->ToolType,
        ];
    }
}