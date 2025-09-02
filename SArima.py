import pymysql
import pandas as pd
from statsmodels.tsa.statespace.sarimax import SARIMAX
import matplotlib.pyplot as plt
from statsmodels.tsa.stattools import adfuller
import schedule
import time

# ฟังก์ชันสำหรับพยากรณ์ค่าฝุ่น PM2.5
def forecast_pm25():
    print("📊 Running PM2.5 Forecast...")

    # เชื่อมต่อกับฐานข้อมูล MySQL
    conn = pymysql.connect(
        host='localhost',
        user='root',
        password='',
        database='iot_class'
    )

# ดึงข้อมูลจากตาราง data_imt_copy
    query = "SELECT time, PM2_5 FROM air_quality_tracker ORDER BY time ASC"
    df = pd.read_sql(query, conn)
    conn.close()

    # แปลงคอลัมน์ time เป็น datetime และตั้งเป็น index
    df['time'] = pd.to_datetime(df['time'])
    df.set_index('time', inplace=True)

    # ตรวจสอบความเป็น Stationary ด้วย ADF Test
    result = adfuller(df['PM2_5'])
    print(f"ADF Statistic: {result[0]}")
    print(f"P-Value: {result[1]}")

    # ถ้า p-value > 0.05 แสดงว่าข้อมูลไม่เป็น stationary -> ทำ Differencing
    if result[1] > 0.05:
        df['PM2_5'] = df['PM2_5'].diff().dropna()

    # สร้างโมเดล SARIMA (p=1, d=1, q=1)(P=1, D=1, Q=1, m=24)
    model = SARIMAX(df['PM2_5'], order=(1, 1, 1), seasonal_order=(1, 1, 1, 24))
    model_fit = model.fit()

    # พยากรณ์ค่า PM2.5 ในอีก 6 ชั่วโมงข้างหน้า
    forecast = model_fit.forecast(steps=6).round(2)

# สร้างช่วงเวลาสำหรับการพยากรณ์
    forecast_times = pd.date_range(start=df.index[-1] + pd.Timedelta(hours=1), periods=6, freq="H")

    # สร้าง DataFrame แสดงผล
    forecast_df = pd.DataFrame({
        'Timestamp': forecast_times,
        'Forecasted PM2.5': forecast
    })

    print(forecast_df)

    # เชื่อมต่อฐานข้อมูลเพื่อบันทึกค่าพยากรณ์
    conn = pymysql.connect(
        host='localhost',
        user='root',
        password='',
        database='iot_class'
    )
    cursor = conn.cursor()

    # บันทึกข้อมูลที่พยากรณ์ลงในตาราง data_predict
    for index, row in forecast_df.iterrows():
        timestamp = row['Timestamp']
        pm25_value = row['Forecasted PM2.5']
        sql = "INSERT INTO data_predict (Time, Pm25) VALUES (%s, %s)"
        cursor.execute(sql, (timestamp, pm25_value))

    conn.commit()
    conn.close()
    print("✅ Forecast saved to database.")

# ตั้งค่าให้ฟังก์ชันรันทุกๆ 1 ชั่วโมงschedule.every(1).hours.do(forecast_pm25)
schedule.every(5).seconds.do(forecast_pm25)


print("⏳ Waiting for next execution...")
while True:
    schedule.run_pending()
    time.sleep(60)  # ตรวจสอบทุกๆ 60 วินาที

# Run : python forecast_pm25.py
