import initSidebar from "./components/sidebar";
import initPasswordToggle from "./components/password-toggle";
import initFormLoadingState from "./components/form-loading-state";
import FileDropzoneUpload from "./features/file-upload/file-dropzone-upload";
import {initDrawers} from "flowbite";

initDrawers();

initSidebar();
initPasswordToggle();
initFormLoadingState();

new FileDropzoneUpload(document).init();
