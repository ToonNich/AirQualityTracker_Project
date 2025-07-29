import mysql.connector
import pandas as pd
import matplotlib.pyplot as plt
from mpl_toolkits.mplot3d import Axes3D
import schedule
import time
from datetime import datetime
import os

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

# ฟังก์ชันสำหรับสร้างกราฟ 3D Scatter Plot และบันทึกลงในไฟล์
def create_and_save_graph(df):
    # สร้าง 3D Scatter Plot
    fig = plt.figure(figsize=(10, 7))
    ax = fig.add_subplot(111, projection='3d')

    # พล็อตข้อมูล
    ax.scatter(df['Temperature'], df['Humidity'], df['PM2_5'], c=df['PM2_5'], cmap='coolwarm')

    # ปรับแต่งกราฟ
    ax.set_xlabel("Temperature (°C)")
    ax.set_ylabel("Humidity (%)")
    ax.set_zlabel("PM2.5")
    plt.title("3D Scatter Plot of PM2.5 vs Temperature & Humidity")

    # สร้างโฟลเดอร์ 'heatmap' หากยังไม่มี
    if not os.path.exists('heatmap'):
        os.makedirs('heatmap')

    # สร้างชื่อไฟล์
    file_name = f"heatmap/heatmap_{datetime.now().strftime('%Y%m%d%H%M%S')}.png"

    # บันทึกกราฟเป็นไฟล์ในโฟลเดอร์ heatmap
    plt.savefig(file_name, format='png')
    plt.close()

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
    file_name = create_and_save_graph(df)

    # แปลงไฟล์เป็น binary
    binary_image = image_to_binary(file_name)

    # บันทึกภาพลงในฐานข้อมูล
    save_image_to_db(file_name, binary_image)

# ตั้งเวลาให้ทำงานทุกๆ 5 วินาที
schedule.every(5).seconds.do(job)

# รันการทำงาน
while True:
    schedule.run_pending()
    time.sleep(1)

# ปิดการเชื่อมต่อฐานข้อมูล
cursor.close()
conn.close()
