from flask import Flask, render_template, request, flash
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.application import MIMEApplication
from reportlab.lib.pagesizes import LETTER
from reportlab.lib import colors
from reportlab.lib.units import inch
from reportlab.platypus import SimpleDocTemplate, Table, TableStyle, Paragraph, Spacer
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_RIGHT
from reportlab.lib.utils import ImageReader
import os
import traceback
from werkzeug.utils import secure_filename

app = Flask(__name__)
app.secret_key = 'your_secret_key'

UPLOAD_DIR = 'invoices'
os.makedirs(UPLOAD_DIR, exist_ok=True)
LOGO_PATH = os.path.join('static', 'logo.png')

def add_background(canvas_obj, doc):
    if os.path.exists(LOGO_PATH):
        logo = ImageReader(LOGO_PATH)
        width, height = LETTER
        img_width = 2.0 * inch
        img_height = 2.0 * inch
        x = (width - img_width) / 2
        y = (height - img_height) / 2 + 1 * inch
        canvas_obj.saveState()
        try:
            canvas_obj.setFillAlpha(0.1)
        except AttributeError:
            pass
        canvas_obj.drawImage(logo, x, y, width=img_width, height=img_height, mask='auto')
        canvas_obj.restoreState()

def generate_invoice(data, filename):
    doc = SimpleDocTemplate(filename, pagesize=LETTER, rightMargin=30, leftMargin=30, topMargin=30, bottomMargin=18)
    styles = getSampleStyleSheet()
    story = []
    right_align = ParagraphStyle(name='RightAlign', parent=styles['Normal'], alignment=TA_RIGHT)
    story.append(Paragraph("<b>INVOICE</b>", styles['Title']))
    story.append(Spacer(1, 12))

    info_data = [
        ['Invoice Date:', data.get('invoice_date', '')],
        ['Invoice #:', data.get('invoice_number', '')],
        ['Terms:', data.get('terms', '')]
    ]
    info_table = Table(info_data, colWidths=[1.5*inch, 2*inch])
    story.append(info_table)
    story.append(Spacer(1, 20))

    ship_to_label = "Ship To" if data.get("client_name") or data.get("client_address") else "TO"
    header_data = [
        [
            Paragraph(
                '<b>FROM:</b><br/>Wild Ewes Woolery<br/>123 Logistics Lane<br/>Denver, CO 80014<br/>Phone: (123) 456-7890',
                styles['Normal']
            ),
            Paragraph(
                f'<b>{ship_to_label}:</b><br/>{data.get("client_name","")}<br/>{data.get("client_address","")}<br/>{data.get("client_email","")}',
                styles['Normal']
            )
        ]
    ]
    header_table = Table(header_data, colWidths=[3.5*inch, 3.5*inch])
    story.append(header_table)
    story.append(Spacer(1, 20))

    descriptions = data.get('description', [])
    rates = data.get('rate', [])
    quantities = data.get('quantity', [])
    amounts = data.get('amount', [])

    if not isinstance(descriptions, list): descriptions = [descriptions]
    if not isinstance(rates, list): rates = [rates]
    if not isinstance(quantities, list): quantities = [quantities]
    if not isinstance(amounts, list): amounts = [amounts]

    item_data = [['DESCRIPTION', 'RATE', 'QTY', 'AMOUNT']]
    for i in range(len(descriptions)):
        desc = descriptions[i] if i < len(descriptions) else ''
        rate = rates[i] if i < len(rates) else ''
        qty = quantities[i] if i < len(quantities) else ''
        amt = amounts[i] if i < len(amounts) else ''
        desc_paragraph = Paragraph(desc.replace('\n', '<br/>') + '<br/><br/><br/>', styles['Normal'])
        item_data.append([desc_paragraph, f"${float(rate):,.2f}" if rate else '', qty, f"${float(amt):,.2f}" if amt else ''])

    total_amount = sum([float(a) if a else 0.0 for a in amounts])
    item_data.append(['', '', Paragraph('<b>Total</b>', styles['Normal']), f"${total_amount:,.2f}"])

    table = Table(item_data, colWidths=[3.5*inch, 1*inch, 1*inch, 1.5*inch])
    table.setStyle(TableStyle([
        ('GRID', (0,0), (-1,-2), 1, colors.black),
        ('LINEABOVE', (-2,-1), (-1,-1), 1, colors.black),
        ('BACKGROUND', (0,0), (-1,0), colors.lightgrey),
        ('FONTNAME', (0,0), (-1,0), 'Helvetica-Bold'),
        ('FONTNAME', (-2,-1), (-1,-1), 'Helvetica-Bold'),
        ('ALIGN', (1,1), (-1,-1), 'CENTER'),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('SPAN', (0,-1), (1,-1)),
    ]))
    story.append(table)
    story.append(Spacer(1, 24))

    story.append(Paragraph("<b>Payment Instructions:</b>", styles['Heading3']))
    payment_text = (
        "Please send payments via ACH to:<br/>"
        "Bank: Wells Fargo Bank<br/>"
        "Routing Number: 123456789<br/>"
        "Account Number: 9876543210<br/><br/>"
        "Or send checks payable to:<br/>"
        "Wild Ewes Woolery<br/>123 Logistics Lane<br/>Denver, CO 80014"
    )
    story.append(Paragraph(payment_text, styles['Normal']))
    story.append(Spacer(1, 24))
    story.append(Paragraph("Thank you for your business!", styles['Italic']))

    doc.build(story, onFirstPage=add_background, onLaterPages=add_background)

# send_email_with_invoice and Flask route remain unchanged


def send_email_with_invoice(to_email, subject, body, attachment_path, data):
    msg = MIMEMultipart('mixed')
    msg['Subject'] = subject
    msg['From'] = 'allengary799@gmail.com'
    msg['To'] = to_email
    msg['Bcc'] = 'alex@wildeweswoolery.com'

    # Create the 'alternative' part
    alt_part = MIMEMultipart('alternative')
    alt_part.attach(MIMEText(body, 'plain'))

    # Build HTML
    html = f"""<html><body>
    <h3>Invoice from Wild Ewes Woolery</h3>
    <p><strong>Invoice Date:</strong> {data.get('invoice_date')}</p>
    <p><strong>Invoice #:</strong> {data.get('invoice_number')}</p>
    <p><strong>Terms:</strong> {data.get('terms')}</p>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr><th>Description</th><th>Rate</th><th>Quantity</th><th>Amount</th></tr>"""

    for desc, rate, qty, amt in zip(data['description'], data['rate'], data['quantity'], data['amount']):
        html += f"<tr><td>{desc}</td><td>{rate}</td><td>{qty}</td><td>${float(amt):,.2f}</td></tr>"

    total = sum([float(a) if a else 0.0 for a in data['amount']])
    html += f"<tr><td colspan='3'><b>Total</b></td><td><b>${total:,.2f}</b></td></tr></table>"
    html += "<p>Thank you for your business!</p></body></html>"

    alt_part.attach(MIMEText(html, 'html'))

    # Attach alt to main message
    msg.attach(alt_part)

    # Attach PDF
    with open(attachment_path, 'rb') as f:
        part = MIMEApplication(f.read(), _subtype='pdf')
        part.add_header('Content-Disposition', 'attachment', filename=os.path.basename(attachment_path))
        msg.attach(part)

    try:
        with smtplib.SMTP('smtp.gmail.com', 587) as smtp:
            smtp.starttls()
            smtp.login('allengary799@gmail.com', 'bphzjgpsalzytymr')
            smtp.send_message(msg)
        return True
    except Exception as e:
        print("Failed to send email:", e)
        return False


@app.route('/', methods=['GET', 'POST'])
def index():
    if request.method == 'POST':
        try:
            def get_form_list(field):
                return [v.strip() for v in request.form.getlist(field) if v.strip()]

            data = {
                'client_name': request.form.get('client_name', '').strip(),
                'client_email': request.form.get('client_email', '').strip(),
                'client_address': request.form.get('client_address', '').strip(),
                'invoice_date': request.form.get('invoice_date', '').strip(),
                'invoice_number': request.form.get('invoice_number', '').strip(),
                'terms': request.form.get('terms', '').strip(),
                'description': get_form_list('description'),
                'rate': get_form_list('rate'),
                'quantity': get_form_list('quantity'),
                'amount': get_form_list('amount'),
            }

            client_name_safe = secure_filename(data['client_name']) or "client"
            filename = os.path.join(UPLOAD_DIR, f"invoice_{client_name_safe}.pdf")

            generate_invoice(data, filename)
            sent = send_email_with_invoice(data['client_email'], f"Invoice #{data['invoice_number']} from Wild Ewes Woolery", "Please find attached your invoice.", filename, data)

            flash("Invoice sent successfully!" if sent else "Invoice generated, but failed to send email.", "success" if sent else "danger")
            return render_template('form.html', data=data)
        except Exception:
            traceback.print_exc()
            flash("An unexpected error occurred. Please try again later.", "danger")
            return render_template('form.html')
    return render_template('form.html')


if __name__ == '__main__':
    app.run(debug=True)