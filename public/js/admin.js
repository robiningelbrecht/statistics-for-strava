import initSidebar from "./components/sidebar";
import initPasswordToggle from "./components/password-toggle";
import initFormLoadingState from "./components/form-loading-state";
import initDispatchCommandForm from "./components/dispatch-command-form";
import initDependentSelects from "./components/dependent-select";
import FileDropzoneUpload from "./features/file-upload/file-dropzone-upload";
import {initImageDropZones} from "./features/file-upload/image-dropzone-upload";
import {initDrawers} from "flowbite";

initDrawers();

initSidebar();
initPasswordToggle();
initFormLoadingState();
initDispatchCommandForm();
initDependentSelects();

new FileDropzoneUpload(document).init();
initImageDropZones(document);
