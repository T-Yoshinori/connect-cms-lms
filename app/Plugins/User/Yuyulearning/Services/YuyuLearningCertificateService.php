<?php

namespace App\Plugins\User\Yuyulearning\Services;

use App\Models\User\YuyuLearning\YuyuLearningCourse;
use App\Models\User\YuyuLearning\YuyuLearningEnrollment;

class YuyuLearningCertificateService
{
    public function make(
        YuyuLearningEnrollment $enrollment,
        YuyuLearningCourse $course,
        string $organizer_name
    ): string {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor($organizer_name);
        $pdf->SetTitle('修了証');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(20, 18, 20);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // 印刷しやすい白地に二重枠の簡潔な修了証とする。
        $pdf->SetDrawColor(70, 70, 70);
        $pdf->SetLineWidth(0.8);
        $pdf->Rect(10, 10, 277, 190);
        $pdf->SetLineWidth(0.25);
        $pdf->Rect(13, 13, 271, 184);

        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetFont('kozminproregular', '', 28);
        $pdf->SetXY(20, 28);
        $pdf->Cell(257, 16, '修 了 証', 0, 1, 'C');

        $pdf->SetFont('kozminproregular', '', 20);
        $pdf->SetXY(35, 65);
        $pdf->Cell(227, 14, (string) $enrollment->user->name . ' 様', 0, 1, 'C');

        $pdf->SetFont('kozminproregular', '', 14);
        $pdf->SetXY(35, 91);
        $pdf->MultiCell(
            227,
            20,
            "あなたは、以下の学習コースを\n修了したことを証します。",
            0,
            'C',
            false
        );

        $pdf->SetFont('kozminproregular', '', 16);
        $pdf->SetXY(45, 126);
        $pdf->MultiCell(207, 16, 'コース名　' . (string) $course->name, 0, 'C', false);

        $pdf->SetFont('kozminproregular', '', 13);
        $pdf->SetXY(45, 151);
        $pdf->Cell(
            207,
            10,
            $enrollment->completed_at->format('Y年n月j日'),
            0,
            1,
            'C'
        );

        $pdf->SetFont('kozminproregular', '', 12);
        $pdf->SetXY(176, 169);
        $pdf->MultiCell(86, 16, "主催者\n" . $organizer_name, 0, 'L', false);

        return $pdf->Output('', 'S');
    }
}
