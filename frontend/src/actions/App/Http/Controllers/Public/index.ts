import PrivateDocumentController from './PrivateDocumentController'
import PartnerApplicationController from './PartnerApplicationController'

const Public = {
    PrivateDocumentController: Object.assign(PrivateDocumentController, PrivateDocumentController),
    PartnerApplicationController: Object.assign(PartnerApplicationController, PartnerApplicationController),
}

export default Public