<?php
// 오류 보고 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 세션 시작
session_start();

// 디렉토리 설정
$imageDir = 'images/';
$outputDir = 'outlines/';

// 디렉토리가 없으면 생성
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// 메시지 및 결과 초기화
$message = '';
$resultImage = '';

// 이미지 경로 가져오기
if (isset($_GET['image'])) {
    $imagePath = $imageDir . $_GET['image'];
    $realImagePath = realpath($imagePath);
    $realImageDir = realpath($imageDir);
    
    // 보안 검사
    if (empty($_GET['image']) || !file_exists($imagePath) || strpos($realImagePath, $realImageDir) !== 0) {
        $_SESSION['outline_result'] = [
            'status' => 'error',
            'message' => '잘못된 이미지 파일입니다.'
        ];
        header('Location: index.php');
        exit;
    }
    
    // 파일 확장자 검사
    $extension = strtolower(pathinfo($_GET['image'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($extension, $allowedExtensions)) {
        $_SESSION['outline_result'] = [
            'status' => 'error',
            'message' => '지원하지 않는 이미지 형식입니다.'
        ];
        header('Location: index.php');
        exit;
    }
    
    // 임계값 설정 (기본값 20)
    $threshold = isset($_GET['threshold']) ? intval($_GET['threshold']) : 20;
    $threshold = max(1, min(100, $threshold)); // 1-100 사이로 제한
    
    // 원본 파일명 및 출력 파일명 설정
    $originalName = pathinfo($_GET['image'], PATHINFO_FILENAME);
    $outputFileName = 'outline_' . $originalName . '.png';
    $outputPath = $outputDir . $outputFileName;
    
    // ImageMagick을 사용하여 윤곽선 추출
    try {
        if (class_exists('Imagick')) {
            // Imagick 클래스 사용
            $imagick = new Imagick($imagePath);
            
            // 크기 제한 (너무 큰 이미지 처리 방지)
            $imagick->thumbnailImage(1000, 1000, true);
            
            // 에지 감지 필터 적용
            $imagick->edgeImage($threshold / 50); // 임계값 조정
            $imagick->negateImage(false);
            
            // 이미지 향상 및 흑백 변환
            $imagick->contrastImage(1);
            $imagick->enhanceImage();
            $imagick->blackThresholdImage('#808080');
            $imagick->whiteThresholdImage('#F0F0F0');
            
            // 윤곽선 이미지 저장
            $imagick->setImageFormat('png');
            $imagick->writeImage($outputPath);
            $imagick->clear();
            
            // 성공 메시지 및 결과 저장
            $_SESSION['outline_result'] = [
                'status' => 'success',
                'message' => '이미지가 성공적으로 변환되었습니다.',
                'output' => $outputFileName
            ];
            
            // 성공 시 새 이미지로 리다이렉트
            header('Location: drawing.php?image='. urlencode('outlines/' . $outputFileName));
            exit;
        } else {
            // Imagick 클래스가 없는 경우 exec으로 시도
            $command = "convert \"$imagePath\" -resize 1000x1000> -edge " . ($threshold / 40) . " -negate -contrast -enhance -threshold 50% \"$outputPath\"";
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                throw new Exception("ImageMagick 실행 오류: " . implode("\n", $output));
            }
            
            // 성공 메시지 및 결과 저장
            $_SESSION['outline_result'] = [
                'status' => 'success',
                'message' => '이미지가 성공적으로 변환되었습니다.',
                'output' => $outputFileName
            ];
            
            // 성공 시 새 이미지로 리다이렉트
            header('Location: drawing.php?image='. urlencode('outlines/' . $outputFileName));
            exit;
        }
    } catch (Exception $e) {
        // 오류 발생 시 세션에 오류 저장
        $_SESSION['outline_result'] = [
            'status' => 'error',
            'message' => '이미지 변환 중 오류가 발생했습니다: ' . $e->getMessage()
        ];
        header('Location: index.php');
        exit;
    }
} else {
    // 이미지가 지정되지 않은 경우
    $_SESSION['outline_result'] = [
        'status' => 'error',
        'message' => '이미지를 선택해주세요.'
    ];
    header('Location: index.php');
    exit;
}
?>