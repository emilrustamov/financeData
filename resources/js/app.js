
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import './bootstrap';
import html2canvas from 'html2canvas';
import { jsPDF } from 'jspdf';

document.getElementById('downloadPdf').addEventListener('click', function () {
    html2canvas(document.querySelector('.pdf-container')).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgWidth = 210;
        const pageHeight = 295;
        const imgHeight = canvas.height * imgWidth / canvas.width;
        let heightLeft = imgHeight;
        let position = 0;

        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        while (heightLeft >= 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }
        pdf.save('dashboard.pdf');
    });
});