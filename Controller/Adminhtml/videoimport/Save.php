<?php
namespace WebTechnologyCodes\VideoImport\Controller\Adminhtml\videoimport;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;


class Save extends \Magento\Backend\App\Action
{
	
	protected $fileSystem;
 
    protected $uploaderFactory;
 
    protected $allowedExtensions = ['csv']; // to allow file upload types 
 
    protected $fileId = 'file'; // name of the input file box  
	
	protected $videoGalleryProcessor;

    public function __construct(
        Action\Context $context,
        Filesystem $fileSystem,
        UploaderFactory $uploaderFactory,
		\WebTechnologyCodes\VideoImport\Model\Product\Gallery\Video\Processor $videoGalleryProcessor
    ) {
        $this->fileSystem = $fileSystem;
        $this->uploaderFactory = $uploaderFactory;
        parent::__construct($context);
		$this->videoGalleryProcessor = $videoGalleryProcessor;
    }

    public function execute()
    {
		/** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
		if($this->getRequest()->getPostValue()){
			$destinationPath = $this->getDestinationPath();
			
			try {
				$uploader = $this->uploaderFactory->create(['fileId' => $this->fileId])
					->setAllowCreateFolders(true)
					->setAllowedExtensions($this->allowedExtensions)
					->addValidateCallback('validate', $this, 'validateFile');
				if (!($result = $uploader->save($destinationPath))) {
					throw new LocalizedException(
						__('File cannot be saved to path: $1', $destinationPath)
					);
				}
				
				$name = $result['name'];
				$filePath = $destinationPath.$name;
				$data = $this->csv_to_array($filePath);
				$this->saveProduct($data);
				
			} catch (\Exception $e) {
				$this->messageManager->addError(
					__($e->getMessage())
				);
			}
			
			$model = $this->_objectManager->create('WebTechnologyCodes\VideoImport\Model\Videoimport');

            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
            }
			//echo $filePath;die;
			$model->setFile($filePath);
			$model->setData($data);

            try {
                $model->setFile($filePath)->save();
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the Videoimport.'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
		}
		return $resultRedirect->setPath('*/*/');
    }
	
	public function saveProduct($data){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		foreach($data as $prd){
			$product = $objectManager->create('\Magento\Catalog\Model\Product');
			$_product = $product->load($product->getIdBySku($prd['sku']));
			if($_product->getId()){
				$prd['image'] = ($_product->getData('thumbnail'))? "catalog/product".$_product->getData('thumbnail') : "";
				//echo $prd['image'] ;exit ;
				$this->saveRecord($_product,$prd);
			}
		}
	}
	
	
	/**
	** Do entry in the database for the video
	**
	*/
    protected function saveRecord($product, $data)
    {
        // Hack, but we need to save data for admin store
        $product->setStoreId($data['store_ids']);

        // Sample video data
        $videoData = [
            'video_id' => $data['video_id'],
            'video_title' => $data['video_title'],
            'video_description' => $data['video_description'],
            'video_provider' => $data['provider'],
            'video_metadata' => null,
            'video_url' => $data['video_url'],
            'media_type' => \Magento\ProductVideo\Model\Product\Attribute\Media\ExternalVideoEntryConverter::MEDIA_TYPE_CODE,
			'file' => $data['image']
        ];

        // TODO: download thumbnail image and save locally under pub/media
        //$videoData['file'] = "catalog/product/4/1/410504970_640.jpg";

        // Add video to the product
        if ($product->hasGalleryAttribute()) {
            $this->videoGalleryProcessor->addVideo(
                $product,
                $videoData,
                ['image', 'small_image', 'thumbnail','disabled'],
                false,
                false
            );
        }
        try{
			$product->save();
			$this->messageManager->addSuccess(__('Videos Successfully Imported'));
		}catch(exception $ex){
			throw new LocalizedException(
						__($ex->getMessage())
					);
		}
    }
    
    public function validateFile($filePath)
    {
        // @todo
        // your custom validation code here
    }
 
    public function getDestinationPath()
    {
        return $this->fileSystem
            ->getDirectoryWrite(DirectoryList::TMP)
            ->getAbsolutePath('/');
    }
	
	public function csv_to_array($filename='', $delimiter=',')
	{
		if(!file_exists($filename) || !is_readable($filename)){
			return FALSE;
		}
		$header = NULL;
		$data = array();
		if (($handle = fopen($filename, 'r')) !== FALSE)
		{
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if(!$header)
					$header = $row;
				else
					$data[] = array_combine($header, $row);
			}
			fclose($handle);
		}
		return $data;
	}
	
}