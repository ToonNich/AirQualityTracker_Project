import mysql.connector
import pandas as pd
import matplotlib.pyplot as plt
import matlab.engine
import os
from datetime import datetime
import schedule
import time

# เชื่อมต่อฐานข้อมูล MySQL
conn = mysql.connector.connect(
    host="localhost",      # ชื่อ host
    user="root",           # ชื่อผู้ใช้งาน
    password="",           # รหัสผ่าน
    database="air"         # ชื่อฐานข้อมูล
)

cursor = conn.cursor()

# ฟังก์ชันสำหรับดึงข้อมูลจากฐานข้อมูล
def get_data_from_db():
    query = "SELECT time, Temperature, Humidity, PM2_5 FROM data_imt_copy"
    df = pd.read_sql(query, conn)
    return df

# ฟังก์ชันสำหรับสร้างกราฟ 3D ด้วย MATLAB และบันทึกลงในไฟล์
def create_and_save_graph_with_matlab(df):
    # เริ่ม MATLAB engine
    eng = matlab.engine.start_matlab()

    # ส่งข้อมูลจาก DataFrame ไปยัง MATLAB
    temperature = matlab.double(df['Temperature'].tolist())
    humidity = matlab.double(df['Humidity'].tolist())
    pm25 = matlab.double(df['PM2_5'].tolist())

    # ใช้ MATLAB สร้างกราฟ 3D
    fig_handle = eng.figure(nargout=1)  # สร้าง figure handle
    eng.scatter3(temperature, humidity, pm25, 20, pm25, 'filled', nargout=0)
    eng.xlabel('Temperature (°C)', nargout=0)
    eng.ylabel('Humidity (%)', nargout=0)
    eng.zlabel('PM2.5', nargout=0)
    eng.title('3D Scatter Plot of PM2.5 vs Temperature & Humidity', nargout=0)

    # สร้างโฟลเดอร์ 'heatmap' หากยังไม่มี
    if not os.path.exists('heatmap'):
        os.makedirs('heatmap')

    # สร้างชื่อไฟล์
    file_name = f"C:/xampp/htdocs/heatmapV1/heatmap_{datetime.now().strftime('%Y%m%d%H%M%S')}.png"

    # บันทึกกราฟเป็นไฟล์ในโฟลเดอร์ heatmap
    eng.saveas(fig_handle, file_name, nargout=0)  # ใช้ figure handle ที่สร้างมา

    # ปิด MATLAB engine
    eng.quit()

    return file_name

# ฟังก์ชันสำหรับแปลงไฟล์เป็นข้อมูลบิต (binary)
def image_to_binary(image_path):
    with open(image_path, 'rb') as file:
        binary_data = file.read()
    return binary_data

# ฟังก์ชันสำหรับบันทึกข้อมูลลงในฐานข้อมูล
def save_image_to_db(file_name, binary_image):
    query = "INSERT INTO heatmap (FileName, Time) VALUES (%s, NOW())"
    cursor.execute(query, (file_name,))  # Only store file name, not binary image
    conn.commit()

# ฟังก์ชันหลักที่รันทุกๆ 5 วินาที
def job():
    # ดึงข้อมูลจากฐานข้อมูล
    df = get_data_from_db()

    # สร้างและบันทึกกราฟ
    file_name = create_and_save_graph_with_matlab(df)

    # แปลงไฟล์เป็น binary
    binary_image = image_to_binary(file_name)

    # บันทึกภาพลงในฐานข้อมูล
    save_image_to_db(file_name, binary_image)

# ตั้งเวลาให้ทำงานทุกๆ 5 วินาที
schedule.every(180).seconds.do(job)

# รันการทำงาน
while True:
    schedule.run_pending()
    time.sleep(1)

# ปิดการเชื่อมต่อฐานข้อมูล
cursor.close()
conn.close()
